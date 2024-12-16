<?php
namespace App\Controller;

use App\Entity\Employee;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class EmployeeController extends AbstractController
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/api/employees', name:'get_employees', methods:['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $name = $request->query->get('name');

        $qb = $em->getRepository(Employee::class)->createQueryBuilder('e')
                ->where('e.deletedAt IS NULL'); // Solo empleados activos

        // Parámetro de búsqueda
        if ($name) {
            $qb->andWhere('e.firstName LIKE :name OR e.lastName LIKE :name')
            ->setParameter('name', '%'.$name.'%');
        }

        $employees = $qb->getQuery()->getArrayResult();

        return new JsonResponse($employees);
    }

    #[Route('/api/employees', name:'create_employee', methods:['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $employee = new Employee();
        $employee->setFirstName($data['firstName']);
        $employee->setLastName($data['lastName']);
        $employee->setPosition($data['position']);
        $employee->setEmail($data['email']);
        $employee->setBirthDate(new \DateTime($data['birthDate']));
        $employee->setCreatedBy($this->getUser());
        $em->persist($employee);
        $em->flush();

        // Servicio de Python para envio de correo
        $pythonServiceUrl = $_ENV['BACKEND_SEND_EMAIL_PY'];; 
        $sharedToken = $_ENV['BACKEND_SHARED_TOKEN_PHP_PY'];

        $payload = [
            'first_name' => $employee->getFirstName(),
            'last_name' => $employee->getLastName(),
            'email' => $employee->getEmail(), 
            'token' => $sharedToken
        ];

        $response = $this->client->request('POST', $pythonServiceUrl, [
            'json' => $payload
        ]);

        if ($response->getStatusCode() !== 200) {
            return new JsonResponse(['error' => 'Failed to send welcome email'], 500);
        }

        return new JsonResponse(['status' => 'Employee created and email sent'], 201);
        // return new JsonResponse(['status' => 'Employee created'], 201);
    }

    #[Route('/api/employees/{id}', name:'update_employee', methods:['PUT'])]
    public function update($id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $employee = $em->getRepository(Employee::class)->find($id);
        if (!$employee) {
            return new JsonResponse(['error' => 'Employee not found'], 404);
        }

        // Chequear permisos (para futuro)
        $employee->setFirstName($data['firstName'] ?? $employee->getFirstName());
        $employee->setLastName($data['lastName'] ?? $employee->getLastName());
        $employee->setPosition($data['position'] ?? $employee->getPosition());
        $em->flush();

        return new JsonResponse(['status' => 'Employee updated']);
    }

    #[Route('/api/employees/{id}', name:'delete_employee', methods:['DELETE'])]
    public function delete($id, EntityManagerInterface $em): JsonResponse
    {
        $employee = $em->getRepository(Employee::class)->find($id);
        if (!$employee) {
            return new JsonResponse(['error' => 'Employee not found'], 404);
        }

        // $em->remove($employee);
        $employee->setDeletedAt(new \DateTime());
        $em->flush();

        // Buscar al usuario asociado por email para hacer el borrado logico
        $user = $em->getRepository(User::class)->findOneBy(['email' => $employee->getEmail()]);
        if ($user && $user->getDeletedAt() === null) {
            
            // Se hace esto para no habilitar la entrada del usuario a fututo
            $user->setEmail( "delete_" . substr(bin2hex(random_bytes(5)), 0, 5) ."_". $employee->getEmail() );

            $user->setDeletedAt(new \DateTime());
            $em->flush();
        }
        
        return new JsonResponse(['status' => 'Employee deleted']);
    }

    
}
