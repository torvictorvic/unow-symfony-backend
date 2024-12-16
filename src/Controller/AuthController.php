<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{

    #[Route('/api/register', name:'api_register', methods:['POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $role = $data['role'] ?? 'EMPLEADO';

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'Email and password are required'], 400);
        }

        $user = new User();
        $user->setEmail($email);
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setRoles([$role]);

        $em->persist($user);
        $em->flush();

        return new JsonResponse(['status' => 'User created'], 201);
    }

}

