<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class AdminController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_getAllUsers', methods: ['GET'])]
    public function getAllUsers(EntityManager $em): JsonResponse
    {
        $users = $em->getRepository(User::class)->findAll();
        $data = [];

        foreach($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'phoneNumber' => $user->getPhoneNumber(),
                'roles' => $user->getRoles(),
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

     #[Route('/admin/users/{id}', name: 'admin_getUser', methods: ['GET'])]
     public function getUserById(EntityManager $em, int $id): JsonResponse
     {
        $user = $em->getRepository(User::class)->find($id);

        if(!$user) {
            return new JsonResponse(["erreur" => "L'utilisateur n'existe pas"], Response::HTTP_NOT_FOUND);
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

     #[Route('/admin/users', name: 'admin_userCreate', methods: ['POST'])]
     public function createUser(Request $request, UserPasswordHasherInterface $ph, EntityManager $em): JsonResponse
     {
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

    #[Route('/admin/users/{id}', name: 'admin_userEdit', methods: ['PUT'])]
    public function editUser(Request $request, User $user, UserPasswordHasherInterface $ph, EntityManager $em, int $id): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);

        if(!$user) {
            return new JsonResponse(["erreur" => "L'utilisateur nexiste pas"], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $alreadyExist = $em->getRepository(User::class)->findBy(['email' => $data['email']]);
            if ($alreadyExist) {
                return new JsonResponse(['erreur' => 'L\utilisateur existe déjà'], Response::HTTP_CONFLICT);
            }
            $user->setEmail($data['email']);
        }

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        if (isset($data['phoneNumber'])) {
            $phoneNumber = $data['phoneNumber'];
            if (!preg_match('/^\d{2} \d{2} \d{2} \d{2} \d{2}$/', $phoneNumber)) {
                return new JsonResponse(['erreur' => 'Format de numéro de téléphone invalide (exemple de format attend : 01 02 03 04 05'], Response::HTTP_BAD_REQUEST);
            }

            $user->setPhoneNumber($phoneNumber);
        }

        if (isset($data['password'])) {
            $user->setPassword($ph->hashPassword($user, $data['password']));
        }

        $em->flush();

        return new JsonResponse(['message' => 'L\utilisateur a bien été mis à jour']);
    }

    #[Route('/admin/users/{id}', name: 'admin_userDelete', methods: ['DELETE'])]
    public function deleteUser(EntityManager $em, int $id): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);

        if(!$user) {
            return new JsonResponse(["erreur" => "L'utilisateur n'existe pas"], Response::HTTP_NOT_FOUND);
        }

        $em->remove($user);
        $em->flush();

        return new JsonResponse(['message' => 'L\'utilisateur a bien été supprimé']);
    }
}
