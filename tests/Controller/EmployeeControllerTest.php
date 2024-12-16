<?php

namespace App\Tests\Controller;

use App\Controller\EmployeeController;
use App\Entity\Employee;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EmployeeControllerTest extends TestCase
{
    /**
     * Testea la lista de empleados sin proporcionar un nombre de búsqueda.
     */
    public function testListEmployeesWithoutName(): void
    {
        // Mockear EntityManagerInterface
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        
        // Mockear EntityRepository para Employee
        $repositoryMock = $this->createMock(EntityRepository::class);
        $entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->with(Employee::class)
            ->willReturn($repositoryMock);

        // Mockear QueryBuilder
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $repositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilderMock);

        // Configurar métodos del QueryBuilder
        $queryBuilderMock->expects($this->once())
            ->method('where')
            ->with('e.deletedAt IS NULL')
            ->willReturnSelf();

        // No se espera que 'andWhere' sea llamado ya que no se proporciona 'name'
        $queryBuilderMock->expects($this->never())
            ->method('andWhere');

        // Mockear Query
        $queryMock = $this->createMock(Query::class);
        $queryBuilderMock->expects($this->once())
            ->method('getQuery')
            ->willReturn($queryMock);

        // Configurar getArrayResult en Query
        $queryMock->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([
                [
                    'id' => 1,
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'position' => 'Developer',
                    'birthDate' => [
                        'date' => '1990-01-01 00:00:00.000000',
                        'timezone_type' => 3,
                        'timezone' => 'America/New_York'
                    ],
                    'createdAt' => [
                        'date' => '2024-12-14 08:48:15.000000',
                        'timezone_type' => 3,
                        'timezone' => 'America/New_York'
                    ],
                    'email' => 'john.doe@example.com'
                ],
                // Se pude agregar más empleados
            ]);

        // Crear una Request sin parámetro 'name'
        $request = new Request();

        // Mockear HttpClientInterface
        $httpClientMock = $this->createMock(HttpClientInterface::class);

        // Instanciar el controlador con el mock de HttpClientInterface
        $employeeController = new EmployeeController($httpClientMock);

        // Ejecutar el método list
        $response = $employeeController->list($request, $entityManagerMock);

        // Verificar que la respuesta sea una instancia de JsonResponse
        $this->assertInstanceOf(JsonResponse::class, $response);

        // Verificar el código de estado y el contenido
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertCount(1, $content);
        $this->assertEquals('John', $content[0]['firstName']);
    }

    /**
     * Testea la creación exitosa de un empleado.
     */
    public function testCreateEmployeeSuccessfully(): void
    {
        // Mockear EntityManagerInterface
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Employee::class));
        $entityManagerMock->expects($this->once())
            ->method('flush');

        // Mockear HttpClientInterface y ResponseInterface
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(), // Puedes especificar la URL si lo deseas
                $this->arrayHasKey('json')
            )
            ->willReturn($responseMock);

        // Mockear el usuario autenticado
        $userMock = $this->createMock(User::class);
        $userMock->expects($this->any())
            ->method('getEmail')
            ->willReturn('jane.doe@example.com');

        // Configurar el método getUser() en el controlador
        $employeeController = $this->getMockBuilder(EmployeeController::class)
            ->setConstructorArgs([$httpClientMock])
            ->onlyMethods(['getUser'])
            ->getMock();
        $employeeController->expects($this->once())
            ->method('getUser')
            ->willReturn($userMock);

        // Crear una Request con datos válidos
        $request = new Request([], [], [], [], [], [], json_encode([
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'position' => 'Developer',
            'email' => 'jane.doe@example.com',
            'birthDate' => '1995-05-15'
        ]));

        // Ejecutar el método create
        $response = $employeeController->create($request, $entityManagerMock);

        // Verificar que la respuesta sea una instancia de JsonResponse
        $this->assertInstanceOf(JsonResponse::class, $response);

        // Verificar el código de estado y el contenido
        $this->assertEquals(201, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Employee created and email sent', $content['status']);
    }

    /**
     * Testea la actualización de un empleado que no existe.
     */
    public function testUpdateEmployeeNotFound(): void
    {
        // Mockear EntityManagerInterface
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $repositoryMock = $this->createMock(EntityRepository::class);
        $repositoryMock->expects($this->once())
            ->method('find')
            ->with(999) // ID inexistente
            ->willReturn(null);

        $entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->with(Employee::class)
            ->willReturn($repositoryMock);

        // Crear una Request con datos para actualizar
        $request = new Request([], [], [], [], [], [], json_encode([
            'firstName' => 'UpdatedName'
        ]));

        // Instanciar el controlador con un mock de HttpClientInterface
        $employeeController = new EmployeeController($this->createMock(HttpClientInterface::class));

        // Ejecutar el método update
        $response = $employeeController->update(999, $request, $entityManagerMock);

        // Verificar que la respuesta sea una instancia de JsonResponse
        $this->assertInstanceOf(JsonResponse::class, $response);

        // Verificar el código de estado y el contenido
        $this->assertEquals(404, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Employee not found', $content['error']);
    }

    /**
     * Testea la eliminación lógica exitosa de un empleado.
     */
    public function testDeleteEmployeeSuccessfully(): void
    {
        // Mockear EntityManagerInterface y repositorio de Employee y User
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $employeeRepositoryMock = $this->createMock(EntityRepository::class);
        $userRepositoryMock = $this->createMock(EntityRepository::class);

        // Crear un empleado mock
        $employeeMock = $this->createMock(Employee::class);
        $employeeMock->expects($this->once())
            ->method('setDeletedAt')
            ->with($this->isInstanceOf(\DateTime::class));
        $employeeMock->expects($this->exactly(2))
            ->method('getEmail')
            ->willReturn('jane.doe@example.com'); // Configurar el email para dos llamadas

        $employeeRepositoryMock->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($employeeMock);

        // Crear un usuario mock asociado
        $userMock = $this->createMock(User::class);
        $userMock->expects($this->once())
            ->method('setEmail')
            ->with($this->stringContains('delete_'));
        $userMock->expects($this->once())
            ->method('setDeletedAt')
            ->with($this->isInstanceOf(\DateTime::class));

        $userRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'jane.doe@example.com']) // Email esperado
            ->willReturn($userMock);

        // Configurar los repositorios en el EntityManager
        $entityManagerMock->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [Employee::class, $employeeRepositoryMock],
                [User::class, $userRepositoryMock],
            ]);

        // Esperar que flush sea llamado dos veces (una para el empleado y otra para el usuario)
        $entityManagerMock->expects($this->exactly(2))
            ->method('flush');

        // Crear una Request
        $request = new Request();

        // Instanciar el controlador con un mock de HttpClientInterface
        $employeeController = new EmployeeController($this->createMock(HttpClientInterface::class));

        // Ejecutar el método delete
        $response = $employeeController->delete(1, $entityManagerMock);

        // Verificar que la respuesta sea una instancia de JsonResponse
        $this->assertInstanceOf(JsonResponse::class, $response);

        // Verificar el código de estado y el contenido
        $this->assertEquals(200, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Employee deleted', $content['status']);
    }
}
