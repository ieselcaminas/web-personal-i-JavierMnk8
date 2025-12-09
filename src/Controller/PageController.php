<?php

namespace App\Controller;

use App\Entity\Contacto;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PageController extends AbstractController
{
    #[Route('/', name: 'inicio')]
    public function inicio(ManagerRegistry $doctrine): Response
    {
        $repositorio = $doctrine->getRepository(Contacto::class);
        $contactos = $repositorio->findAll();

        return $this->render('inicio.html.twig', [
            'contactos' => $contactos
        ]);
    }
    
    // Ruta ficticia /index solicitada en el ejercicio para las redirecciones de seguridad
    #[Route('/index', name: 'index_login')]
    public function index(): Response 
    {
        return new Response("<body>Por favor, inicie sesión (Ruta /index)</body>");
    }
}