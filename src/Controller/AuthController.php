<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $ph): JsonResponse {

        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['name'], $data['phoneNumber'])) {
            return new JsonResponse(['erreur' => 'Veuillez remplir tous les champs'], Response::HTTP_BAD_REQUEST);
        }

        $alreadyExist = $em->getRepository(User::class)->findBy(['email' => $data['email']]);
        if ($alreadyExist) {
            return new JsonResponse(['erreur' => 'L\utilisateur existe déjà'], Response::HTTP_CONFLICT);
        }

        $phoneNumber = $data['phoneNumber'];
        if (!preg_match('/^\d{2} \d{2} \d{2} \d{2} \d{2}$/', $phoneNumber)) {
            return new JsonResponse(['erreur' => 'Format de numéro de téléphone invalide (exemple de format attend : 01 02 03 04 05'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($ph->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);
        $user->setName($data['name']);
        $user->setPhoneNumber($phoneNumber);

        $em->persist($user);
        $em->flush();

        return new JsonResponse(['message' => 'L\'utilisateur a été crée avec succès'], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return new JsonResponse(['message' => 'L\'utilisateur est bien connecté'], Response::HTTP_OK);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return new JsonResponse(['message' => 'L\'utilisateur a été decconecté avec succès'], Response::HTTP_OK);
    }
}
