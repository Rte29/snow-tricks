<?php

namespace App\Controller;

use App\Entity\Figure;
use App\Form\FigureType;
use App\Repository\MediaRepository;
use App\Repository\FigureRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FigureController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(Figure::class);
        $figures = $repo->findBy([], ['createdAt' => 'DESC']);

        return $this->render(
            'blog/home.html.twig',
            [
                'controler_name' => 'FigureController',
                'figures' => $figures
            ]
        );
    }

    #[Route('/ajouter', name: 'app_add_figure')]
    #[Route('/{id}/modifier', name: 'app_edit_figure')]
    public function form(Figure $figure = null, Request $request, EntityManagerInterface $manager, FigureRepository $figureRepo, CategoryRepository $categoryRepo): Response
    {

        if ($this->getUser() == null) {
            $this->addFlash('danger', 'Vous devez être connecté pour ajouter une figure');
            return $this->redirectToRoute('app_home');
        }
        if (!$figure) {
            $figure = new Figure;
        }

        //ajout d'utilisateur en session
        $user = $this->getUser();
        $groups = $categoryRepo->findAll();
        $groupsFigure = [];



        foreach ($groups as $group) {
            $groupsFigure[$group->getFigureCategory()] = $group->getFigureCategory();
        }
        $form = $this->createForm(FigureType::class, $figure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $title = $figure->getTitle();
            $slugger = new AsciiSlugger();
            $slug = strtolower($slugger->slug($title, '-'));
            $i = 0;

            do {
                $existSlug = $figureRepo->findOneBy([
                    'slug' => $slug
                ]);
                if ($existSlug != null) {
                    $slug = strtolower($slugger->slug($title, '-') . '-' . $i);
                    $i++;
                }
            } while ($existSlug != null);
            $figure->setSlug($slug);
            if (!$figure->getId()) {
                $figure->setCreatedAt(new \DateTimeImmutable());
            } else {
                $figure->setUpdateAt(new \DateTimeImmutable());
            }
            $figure->setUser($user);

            $manager->persist($figure);
            $manager->flush();
            $this->addFlash("success", "La figure a bien été ajouté");

            return $this->redirectToRoute('app_home');
        }
        return $this->render('figure/add_figure.html.twig', [
            'form' => $form->createView(),
            'editMode' => $figure->getId() !== null

        ]);
    }

    #[Route('/figure/{id}', name: 'app_show_figure')]
    public function show(EntityManagerInterface $em, Figure $figure, FigureRepository $figureRepo, $id): Response
    {
        $repo = $em->getRepository(Figure::class);
        $figure = $repo->find($id);
        return $this->render('figure/show_figure.html.twig', [
            'figure' => $figure
        ]);
    }
}
