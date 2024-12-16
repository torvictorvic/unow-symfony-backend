<?php

namespace App\Tests\Unit\Controller;

use App\Controller\AuthController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends TestCase
{
    public function testRegisterUserWithoutEmailAndPasswordReturnsError(): void
    {
        // Mockear EntityManagerInterface
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        // No se espera que se llame a persist o flush
        $entityManagerMock->expects($this->never())->method('persist');
        $entityManagerMock->expects($this->never())->method('flush');

        // Mockear UserPasswordHasherInterface
        $passwordHasherMock = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasherMock->expects($this->never())->method('hashPassword');

        // Crear una Request con datos vacíos
        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => '',
            'password' => '',
        ]));

        // Instanciar el controlador
        $controller = new AuthController();

        // Ejecutar el método register
        $response = $controller->register($request, $entityManagerMock, $passwordHasherMock);

        // Verificar que la respuesta sea una instancia de JsonResponse
        $this->assertInstanceOf(JsonResponse::class, $response);

        // Verificar el código de estado y el contenido
        $this->assertEquals(400, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('Email and password are required', $content['error']);
    }

    public function testRegisterUserSuccessfully(): void
    {
        // Mockear EntityManagerInterface
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        // Espera que se llame a persist y flush una vez
        $entityManagerMock->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $entityManagerMock->expects($this->once())->method('flush');

        // Mockear UserPasswordHasherInterface
        $passwordHasherMock = $this->createMock(UserPasswordHasherInterface::class);
        // Configurar el mock para que devuelva una contraseña hasheada
        $passwordHasherMock->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        // Crear una Request con datos válidos
        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => 'test'.uniqid().'@example.com',
            'password' => 'secret',
            'role' => 'USUARIO'
        ]));

        // Instanciar el controlador
        $controller = new AuthController();

        // Ejecutar el método register
        $response = $controller->register($request, $entityManagerMock, $passwordHasherMock);

        // Verificar que la respuesta sea una instancia de JsonResponse
        $this->assertInstanceOf(JsonResponse::class, $response);

        // Verificar el código de estado y el contenido
        $this->assertEquals(201, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('User created', $content['status']);
    }
}
