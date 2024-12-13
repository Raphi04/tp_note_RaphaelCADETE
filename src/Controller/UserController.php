<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/user/profile', name: 'user_profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['erreur' => 'Utilisateur non trouvé.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'phoneNumber' => $user->getPhoneNumber(),
            'roles' => $user->getRoles(),
        ];

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/user/profile', name: 'user_profileUpdate', methods: ['PUT'])]
    public function updateProfile(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['erreur' => 'Utilisateur non trouvé.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        if (isset($data['email'])) {
            $alreadyExist = $em->getRepository(User::class)->findBy(['email' => $data['email']]);
            if ($alreadyExist) {
                return new JsonResponse(['erreur' => 'L\utilisateur existe déjà'], Response::HTTP_CONFLICT);
            }
            
            $user->setEmail($data['email']);
        }

        if (isset($data['phoneNumber'])) {
            $phoneNumber = $data['phoneNumber'];
            if (!preg_match('/^\d{2} \d{2} \d{2} \d{2} \d{2}$/', $phoneNumber)) {
                return new JsonResponse(['erreur' => 'Format de numéro de téléphone invalide (exemple de format attend : 01 02 03 04 05'], Response::HTTP_BAD_REQUEST);
            }
            $user->setPhoneNumber($phoneNumber);
        }

        $em->persist($user);
        $em->flush();

        return new JsonResponse(['message' => 'Votre profil à été modifié avec succès',], Response::HTTP_OK);
    }
}
