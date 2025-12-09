<?php

namespace App\Controller;

use App\Entity\Contacto;
use App\Entity\Provincia;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ContactoFormType as ContactoType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

final class ContactoController extends AbstractController
{
    private $contactos = [
        1 => ["nombre" => "Juan Pérez", "telefono" => "524142432", "email" => "juanp@ieselcaminas.org"],
        2 => ["nombre" => "Ana López", "telefono" => "58958448", "email" => "anita@ieselcaminas.org"],
        5 => ["nombre" => "Mario Montero", "telefono" => "5326824", "email" => "mario.mont@ieselcaminas.org"],
        7 => ["nombre" => "Laura Martínez", "telefono" => "42898966", "email" => "lm2000@ieselcaminas.org"],
        9 => ["nombre" => "Nora Jover", "telefono" => "54565859", "email" => "norajover@ieselcaminas.org"]
    ];

    #[Route('/contacto/nuevo', name: 'nuevo')]
    public function nuevo(ManagerRegistry $doctrine, Request $request): Response 
    {
        // 1. Comprobar seguridad
        if (!$this->getUser()) {
            return $this->redirect('/index');
        }

        $contacto = new Contacto();
        $formulario = $this->createForm(ContactoType::class, $contacto);
        
        // Añadimos solo el botón de guardar para el nuevo contacto
        $formulario->add('save', SubmitType::class, ['label' => 'Insertar Contacto']);
        
        $formulario->handleRequest($request);

        if ($formulario->isSubmitted() && $formulario->isValid()) {
            $contacto = $formulario->getData();
            $entityManager = $doctrine->getManager();
            $entityManager->persist($contacto);
            $entityManager->flush();
            return $this->redirectToRoute('inicio');
        }

        return $this->render('nuevo.html.twig', [
            'formulario' => $formulario->createView()
        ]);
    }

    #[Route('/contacto/editar/{codigo}', name: 'editar', requirements:["codigo"=>"\d+"])]
    public function editar(ManagerRegistry $doctrine, Request $request, int $codigo): Response 
    {
        // 1. Comprobar seguridad
        if (!$this->getUser()) {
            return $this->redirect('/index');
        }

        $repositorio = $doctrine->getRepository(Contacto::class);
        $contacto = $repositorio->find($codigo);

        if (!$contacto) {
            return $this->redirectToRoute('inicio');
        }

        // Creas el formulario base
        $formulario = $this->createForm(ContactoType::class, $contacto);

        // 2. LÓGICA DE VARIOS BOTONES:
        // Añadimos dinámicamente los botones aquí en lugar de en el FormType
        // para tener control sobre la lógica de borrado/edición en el controlador.
        $formulario->add('save', SubmitType::class, ['label' => 'Guardar Cambios', 'attr' => ['class' => 'btn-primary']]);
        $formulario->add('delete', SubmitType::class, ['label' => 'Borrar Contacto', 'attr' => ['class' => 'btn-danger']]);

        $formulario->handleRequest($request);

        if ($formulario->isSubmitted() && $formulario->isValid()) {
            $entityManager = $doctrine->getManager();

            // 3. Comprobar qué botón se pulsó
            if ($formulario->get('save')->isClicked()) {
                // CASO EDITAR
                $contacto = $formulario->getData();
                $entityManager->persist($contacto);
                $entityManager->flush();
                
                // Redirigir o mostrar mensaje de éxito
                return $this->redirectToRoute('inicio'); // O volver a 'editar'
                
            } elseif ($formulario->get('delete')->isClicked()) {
                // CASO BORRAR
                $entityManager->remove($contacto);
                $entityManager->flush();
                
                return $this->redirectToRoute('inicio');
            }
        }

        return $this->render('nuevo.html.twig', [
            'formulario' => $formulario->createView(),
            'contacto' => $contacto // Pasamos el contacto por si queremos mostrar detalles fuera del form
        ]);
    }


    #[Route('/contacto/update/{id}/{nombre}', name:'modificar_contacto')]
    public function update(ManagerRegistry $doctrine, $id, $nombre): Response
    {
        $entityManager = $doctrine->getManager();
        $repositorio = $doctrine->getRepository(Contacto::class);
        $contacto = $repositorio->find($id);

        if ($contacto) {
            $contacto->setNombre($nombre);
            try {
                $entityManager->flush();
                return $this->render('ficha_contacto.html.twig', [
                    'contacto' => $contacto
                ]);
            } catch (\Exception $e) {
                return new Response("Error insertando objetos");
            }
        } else {
            return $this->render('ficha_contacto.html.twig', [
                'contacto' => null
            ]);
        }
    }


    #[Route('/contacto/delete/{id}', name:'eliminar_contacto', methods: ['POST'])]
    public function delete(ManagerRegistry $doctrine, $id): Response
    {
        $entityManager = $doctrine->getManager();
        $repositorio = $doctrine->getRepository(Contacto::class);
        $contacto = $repositorio->find($id);

        if ($contacto) {
            try {
                $entityManager->remove($contacto);
                $entityManager->flush();
                return new Response("Contacto eliminado");
            } catch (\Exception $e) {
                return new Response("Error eliminado objeto");
            }
        } else {
            return $this->render('ficha_contacto.html.twig', [
                'contacto' => null
            ]);
        }
    }

    #[Route('/contacto/insertarConProvincia', name: 'insertar_con_provincia_contacto')]
    public function insertarConProvincia(ManagerRegistry $doctrine): Response{
        $entityManager = $doctrine->getManager();
        $provincia = new Provincia();

        $provincia->setNombre("Alicante");
        $contacto = new Contacto();

        $contacto->setNombre("Inserción de prueba con provincia");
        $contacto->setTelefono("900220022");
        $contacto->setEmail("insercion.de.prueba.provincia@contacto.es");
        $contacto->setProvincia($provincia);

        $entityManager->persist($provincia);
        $entityManager->persist($contacto);
        
        $entityManager->flush();
        return $this->render('ficha_contacto.html.twig', [
            'contacto' => $contacto
        ]);
    }

    #[Route('/contacto/insertarSinProvincia', name: 'insertar_sin_provincia_contacto')]
    public function insertarSinProvincia(ManagerRegistry $doctrine): Response{
        $entityManager = $doctrine->getManager();
        $repositorio = $doctrine->getRepository(Provincia::class);

        $provincia = $repositorio->findOneBy(['nombre'=> 'Alicante']);
        
        $contacto = new Contacto();

        $contacto->setNombre("Inserción de prueba sin provincia");
        $contacto->setTelefono("900220022");
        $contacto->setEmail("insercion.de.prueba.sin.provincia@contacto.es");
        $contacto->setProvincia($provincia);

        $entityManager->persist($contacto);
        
        $entityManager->flush();
        return $this->render('ficha_contacto.html.twig', [
            'contacto' => $contacto
        ]);
    }

    
    #[Route('/contacto/buscar/{texto}', name:'buscar_contacto')]
    public function findByName($text): array{
    $qb = $this->createQueryBuilder('c')
        ->andWhere('c.nombre LIKE :text')
        ->setParameter('text', '%' . $text . '%')
        ->getQuery();

    return $qb->execute();
    }
 

    public function buscar(ManagerRegistry $doctrine, $texto): Response {
        // Filtramos aquellos que contengan dicho texto en el nombre
        $repositorio = $doctrine->getRepository(Contacto::class);

        $contactos = $repositorio->findByName($texto);

        return $this->render('lista_contactos.html.twig', [
            'contactos' => $contactos
        ]);
    }


    #[Route('/contacto/{codigo?1}', name: 'ficha_contacto')]
    public function ficha(ManagerRegistry $doctrine, $codigo): Response{
        $repositorio = $doctrine->getRepository(Contacto::class);
        $contacto = $repositorio->find($codigo);

        if ($contacto){
            return $this->render('ficha_contacto.html.twig', ['contacto' => $contacto]);
        }
        return new Response("<html lang='en'<body>Contacto $codigo no encontrado</body></html>");
    }  

}
