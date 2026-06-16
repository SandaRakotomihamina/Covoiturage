<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Force reload from DB to ensure we always display the latest data
        if ($entityManager->contains($user)) {
            $entityManager->refresh($user);
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/settings', name: 'app_profile_settings')]
    #[IsGranted('ROLE_USER')]
    public function settings(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Force reload from DB to ensure we always display the latest data
        // This is necessary because the user might be cached in the security token
        if ($entityManager->contains($user)) {
            $entityManager->refresh($user);
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action', 'save');
            $confirmPassword = $request->request->get('confirm_password');

            if ($action === 'delete_account') {
                if (!$confirmPassword || !$passwordHasher->isPasswordValid($user, $confirmPassword)) {
                    $this->addFlash('error', 'Mot de passe incorrect.');
                    return $this->render('profile/settings.html.twig', ['user' => $user]);
                }

                $entityManager->remove($user);
                $entityManager->flush();
                $request->getSession()->invalidate();
                $this->addFlash('success', 'Votre compte a bien été supprimé.');
                return $this->redirectToRoute('app_default');
            }

            $changePasswordRequested = $request->request->get('change_password') === '1';

            // Only require current password when changing password
            if ($changePasswordRequested) {
                if (!$confirmPassword || !$passwordHasher->isPasswordValid($user, $confirmPassword)) {
                    $this->addFlash('error', 'Mot de passe actuel invalide.');
                    return $this->render('profile/settings.html.twig', ['user' => $user]);
                }
            }

            $user->setName($request->request->get('name'));
            $this->updateUserCarInfo($user, $request);

            if ($changePasswordRequested) {
                $newPassword = trim((string)$request->request->get('new_password'));
                if ($newPassword !== '') {
                    $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Paramètres mis à jour avec succès !');
            return $this->redirectToRoute('app_profile_settings');
        }

        return $this->render('profile/settings.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/settings/car', name: 'app_profile_car_save', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function saveCarInfo(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $hasCar = $request->request->get('has_car') === '1';
        $removeCarImage = $request->request->get('remove_car_image') === '1';
        $uploadedImage = $request->files->get('image_car');

        if ($hasCar) {
            $carModel = trim((string) $request->request->get('car_model'));
            $availableSeats = (int) $request->request->get('available_seats');

            if ($carModel === '' || $availableSeats < 1 || $availableSeats > 8) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Veuillez renseigner un modèle de voiture et un nombre de places valides.'
                ], Response::HTTP_BAD_REQUEST);
            }

            $user->setCarModel($carModel);
            $user->setAvailableSeats($availableSeats);
        } else {
            $user->setCarModel(null);
            $user->setAvailableSeats(null);
        }

        $user->setHasCar($hasCar);
        $this->handleCarImageUpload($user, $uploadedImage, $removeCarImage || !$hasCar);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    private function updateUserCarInfo(User $user, Request $request): void
    {
        $hasCar = $request->request->get('has_car') === '1';
        $user->setHasCar($hasCar);
        $removeCarImage = $request->request->get('remove_car_image') === '1';
        $uploadedImage = $request->files->get('image_car');

        if ($hasCar) {
            $carModel = trim((string) $request->request->get('car_model'));
            $availableSeats = (int) $request->request->get('available_seats');
            $user->setCarModel($carModel !== '' ? $carModel : null);
            $user->setAvailableSeats($availableSeats >= 1 ? $availableSeats : null);
        } else {
            $user->setCarModel(null);
            $user->setAvailableSeats(null);
        }

        $this->handleCarImageUpload($user, $uploadedImage, $removeCarImage || !$hasCar);
    }

    private function handleCarImageUpload(User $user, ?UploadedFile $uploadedImage, bool $remove = false): void
    {
        if ($remove) {
            $this->removeCarImage($user);
            return;
        }

        if (!$uploadedImage instanceof UploadedFile) {
            return;
        }

        $mimeType = $uploadedImage->getMimeType();
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return;
        }

        $uploadDir = $this->getParameter('car_images_directory');
        $basePath = rtrim((string) $this->getParameter('car_images_base_path'), '/');

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $extension = $uploadedImage->guessExtension() ?: 'jpg';
        $newFilename = uniqid('car_', true) . '.' . $extension;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newFilename;

        if (!$this->resizeSquareImage($uploadedImage->getPathname(), $targetPath)) {
            try {
                $uploadedImage->move($uploadDir, $newFilename);
            } catch (FileException $exception) {
                return;
            }
        }

        $this->removeCarImage($user);
        $user->setImageCar($basePath . '/' . $newFilename);
    }

    private function resizeSquareImage(string $sourcePath, string $targetPath, int $size = 800): bool
    {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        [$width, $height, $type] = $imageInfo;
        if ($width <= 0 || $height <= 0) {
            return false;
        }

        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }

        $minSide = min($width, $height);
        $srcX = (int) floor(($width - $minSide) / 2);
        $srcY = (int) floor(($height - $minSide) / 2);

        $squareImage = imagecreatetruecolor($size, $size);
        imagefill($squareImage, 0, 0, imagecolorallocate($squareImage, 255, 255, 255));
        imagecopyresampled(
            $squareImage,
            $sourceImage,
            0,
            0,
            $srcX,
            $srcY,
            $size,
            $size,
            $minSide,
            $minSide
        );

        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($squareImage, $targetPath, 90);
                break;
            case IMAGETYPE_PNG:
                imagealphablending($squareImage, false);
                imagesavealpha($squareImage, true);
                $result = imagepng($squareImage, $targetPath, 6);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    $result = imagewebp($squareImage, $targetPath, 90);
                }
                break;
        }

        imagedestroy($sourceImage);
        imagedestroy($squareImage);

        return $result;
    }

    private function removeCarImage(User $user): void
    {
        $currentImage = $user->getImageCar();
        if (!$currentImage) {
            $user->setImageCar(null);
            return;
        }

        $uploadDir = $this->getParameter('car_images_directory');
        $currentPath = $uploadDir . DIRECTORY_SEPARATOR . basename($currentImage);
        if (is_file($currentPath)) {
            @unlink($currentPath);
        }

        $user->setImageCar(null);
    }
}