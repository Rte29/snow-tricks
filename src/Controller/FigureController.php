<?php

namespace App\Controller;

use App\Entity\Figure;
use App\Form\FigureType;
use App\Repository\FigureRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FigureController extends AbstractController
{
    #[Route('/figure', name: 'app_figure')]
    public function index(): Response
    {
        return $this->render('figure/show_figure.html.twig', [
            'controller_name' => 'FigureController',
        ]);
    }

    #[Route('/figure/ajouter', name: 'app_add-figure')]
    public function add(Request $request, EntityManagerInterface $manager, FigureRepository $figureRepo, CategoryRepository $categoryRepo): Response
    {

        if ($this->getUser() == null) {
            $this->addFlash('error', 'Vous devez être connecté');
            return $this->redirectToRoute('app_home');
        }
        $figure = new Figure;
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

            $figure->setCreatedAt(new \DateTimeImmutable());
            $figure->setUser($user);

            $manager->persist($figure);
            $manager->flush();
            $this->addFlash("success", "La figure a bien été ajouté");

            return $this->redirectToRoute('app_figure');
        }
        return $this->render('blog/add_figure.html.twig', [
            'form' => $form->createView(),

        ]);
    }
}
