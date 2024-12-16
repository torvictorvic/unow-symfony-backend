<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class PositionController extends AbstractController
{
    #[Route('/api/positions', name:'get_positions', methods:['GET'])]
    public function positions(): JsonResponse
    {
        $json = file_get_contents($_ENV['BACKEND_URL_POSITIOS']);
        $data = json_decode($json, true);

        return new JsonResponse($data);
    }
}
