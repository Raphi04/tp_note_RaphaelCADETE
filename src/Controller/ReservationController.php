<?php 

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{

    #[Route('/admin/reservations', name: 'admin_getAllReservation', methods: ['GET'])]
    public function adminGetAllReservation(EntityManagerInterface $em): JsonResponse
    {
        $reservations = $em->getRepository(Reservation::class)->findAll();

        $data = [];
        foreach ($reservations as $reservation) {
            $data[] = [
                'id' => $reservation->getId(),
                'date' => $reservation->getDate()->format('Y-m-d H:i:s'),
                'timeSlot' => $reservation->getTimeSlot(),
                'eventName' => $reservation->getEventName(),
                'user' => $reservation->getUser()->getEmail(),
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/admin/reservations', name: 'admin_reservationCreate', methods: ['POST'])]
    public function adminCreateReservation(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['date'], $data['timeSlot'], $data['eventName'], $data['userId'])) {
            return new JsonResponse(['erreur' => 'Veuillez remplir tous les champs'], Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match("/^([01][0-9]|2[0-3]):([0-5][0-9])-(?:([01][0-9]|2[0-3]):([0-5][0-9]))$/", $data['timeSlot'])) {
            return new JsonResponse(["error" => "Le format de la plage horaire doit être dans la forme HH:MM-HH:MM, par exemple 18:00-20:00."], Response::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return new JsonResponse(["erreur" => "L'utilisateur n'existe pas"], Response::HTTP_NOT_FOUND);
        }

        $reservation = new Reservation();

        $date = new \DateTime($data["date"]);
        $reservation->setDate($date);

        $reservation->setTimeSlot($data['timeSlot']);
        $reservation->setEventName($data['eventName']);
        $reservation->setUser($user);

        $now = new \DateTime();
        $reservationDate = $reservation->getDate();
        $interval = $now->diff($reservationDate);
        if ($interval->h < 24) {
            return new JsonResponse(["erreur" => "Les réservations doivent être effectuées au moins 24 heures à l'avance."], Response::HTTP_BAD_REQUEST);
        }

        $existingReservation = $em->getRepository(Reservation::class)->findOneBy([
            'date' => $reservation->getDate(),
            'timeSlot' => $reservation->getTimeSlot(),
        ]);

        if ($existingReservation) {
            return new JsonResponse(["error" => "Cette plage horaire est déjà réservée."], Response::HTTP_CONFLICT);
        }

        $em->persist($reservation);
        $em->flush();

        return new JsonResponse(["message" => "La réservation a bien été crée"], Response::HTTP_OK);
    }

    #[Route('/admin/reservations/{id}', name: 'admin_reservationUpdate', methods: ['PUT'])]
    public function adminUpdateReservation(Request $request, EntityManagerInterface $em, int $id): JsonResponse
    {
        $reservation = $em->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            return new JsonResponse(["erreur" => "La réservation n'existe pas"], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['date'])) {
            $date = new \DateTime($data["date"]);
            $reservation->setDate($date);
        }
        if (isset($data['timeSlot'])) {
            if (!preg_match("/^([01][0-9]|2[0-3]):([0-5][0-9])-(?:([01][0-9]|2[0-3]):([0-5][0-9]))$/", $data['timeSlot'])) {
                return new JsonResponse(["error" => "Le format de la plage horaire doit être dans la forme HH:MM-HH:MM, par exemple 18:00-20:00."], Response::HTTP_BAD_REQUEST);
            }
            $reservation->setTimeSlot($data['timeSlot']);
        }

        if (isset($data['eventName'])) {
            $reservation->setEventName($data['eventName']);
        }

        $now = new \DateTime();
        $reservationDate = $reservation->getDate();
        $interval = $now->diff($reservationDate);
        if ($interval->h < 24) {
            return new JsonResponse(["erreur" => "Les réservations doivent être effectuées au moins 24 heures à l'avance."], Response::HTTP_BAD_REQUEST);
        }

        $existingReservation = $em->getRepository(Reservation::class)->findOneBy([
            'date' => $reservation->getDate(),
            'timeSlot' => $reservation->getTimeSlot(),
        ]);

        if ($existingReservation) {
            return new JsonResponse(["erreur" => "Cette plage horaire est déjà réservée."], Response::HTTP_CONFLICT);
        }

        $em->flush();

        return new JsonResponse(["message" => "La reservation a bien été mis à jour"], Response::HTTP_OK);
    }

    #[Route('/admin/reservations/{id}', name: 'admin_reservationDelete', methods: ['DELETE'])]
    public function adminDeleteReservation(EntityManagerInterface $em, int $id): JsonResponse
    {
        $reservation = $em->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            return new JsonResponse(["erreur" => "La réservation n'existe pas"], Response::HTTP_NOT_FOUND);
        }

        $em->remove($reservation);
        $em->flush();

        return new JsonResponse(["message" => "La réservation a été supprimée avec succès"], Response::HTTP_NO_CONTENT);
    }

    #[Route('/user/reservation', name: 'user_createReservation', methods: ['POST'])]
    public function createReservation(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $reservation = new Reservation();
        $reservation->setDate(new \DateTime($data['date']));
        $reservation->setTimeSlot($data['timeSlot']);
        $reservation->setEventName($data['eventName']);
        $reservation->setUser($user);

        $now = new \DateTime();
        $reservationDate = $reservation->getDate();
        $interval = $now->diff($reservationDate);
        if ($interval->h < 24) {
            return new JsonResponse(["erreur" => "Les réservations doivent être effectuées au moins 24 heures à l'avance."], Response::HTTP_BAD_REQUEST);
        }

        $existingReservation = $em->getRepository(Reservation::class)->findOneBy([
            'date' => $reservation->getDate(),
            'timeSlot' => $reservation->getTimeSlot(),
        ]);

        if ($existingReservation) {
            return new JsonResponse(['error' => 'Cette plage horaire est déjà réservée.'], Response::HTTP_CONFLICT);
        }

        $em->persist($reservation);
        $em->flush();

        return new JsonResponse(['message' => 'Réservation créée avec succès.'], Response::HTTP_CREATED);
    }

        #[Route('/user/reservations', name: 'user_getReservations', methods: ['GET'])]
        public function getReservations(EntityManagerInterface $em): JsonResponse
        {
            $user = $this->getUser();
    
            if (!$user) {
                return new JsonResponse(['error' => 'Utilisateur non trouvé.'], Response::HTTP_UNAUTHORIZED);
            }
    
            $reservations = $em->getRepository(Reservation::class)->findBy(['user' => $user->getEmail()]);
    
            foreach ($reservations as $reservation) {
                $data[] = [
                    'id' => $reservation->getId(),
                    'date' => $reservation->getDate()->format('Y-m-d H:i:s'),
                    'timeSlot' => $reservation->getTimeSlot(),
                    'eventName' => $reservation->getEventName(),
                ];
            }
    
            return new JsonResponse($data, Response::HTTP_OK);
        }
}
