<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Media;
use App\Entity\Figure;
use App\Entity\Comment;
use App\Entity\Category;
use App\Form\FigureType;
use Doctrine\ORM\EntityManager;
use App\Repository\MediaRepository;
use App\Repository\FigureRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Helper\Dumper;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FigureController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $em, Request $request, PaginatorInterface $paginator): Response
    {
        $repo = $em->getRepository(Figure::class);
        $figures = $repo->findBy([], ['createdAt' => 'DESC']);

        $figuresAll = $paginator->paginate(
            $figures,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render(
            'blog/home.html.twig',
            [
                'controler_name' => 'FigureController',
                'figures' => $figuresAll
            ]
        );
    }

    #[Route('/profil', name: 'app_profil')]
    public function profil(): Response
    {
        $user = $this->getUser();
        return $this->render(
            'registration/profil.html.twig',
            [
                'user' => $user
            ]
        );
    }

    #[Route('/figures/ajouter', name: 'app_add_figure')]
    #[Route('/figures/modifier/{slug}', name: 'app_edit_figure')]
    public function editFigure(Figure $figure = null, Request $request, EntityManagerInterface $em, FigureRepository $figureRepo, MediaRepository $mediaRepo, CategoryRepository $categoryRepo, $slug = null): Response
    {

        if ($this->getUser() == null) {
            $this->addFlash('danger', 'Vous devez être connecté pour ajouter ou modifier une figure');
            return $this->redirectToRoute('app_home');
        }
        if (!$figure) {
            $figure = new Figure;
        }

        $user = $this->getUser();
        //category
        $groups = $categoryRepo->findAll();
        $groupsFigure = [];

        foreach ($groups as $group) {
            $groupsFigure[$group->getFigureCategory()] = $group->getFigureCategory();
        }
        $form = $this->createForm(FigureType::class, $figure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //slug
            $title = $figure->getTitle();
            $slugger = new AsciiSlugger();
            $slug = strtolower($slugger->slug($title, '-'));
            $i = 0;

            $existFigure = $figureRepo->findOneBy(['title' => $title]);

            if (!$figure->getId() && $existFigure != null) {
                $this->addFlash('warning', 'cette figure existe déjà !');
                return $this->redirectToRoute('app_show_figure', ['slug' => $slug]);
            }
            if ($existFigure != null) {
                if ($figure->getId() != $existFigure->getId()) {
                    $this->addFlash('warning', 'cette figure existe déjà !');
                    return $this->redirectToRoute('app_show_figure', ['slug' => $slug]);
                }
            }



            if ($existFigure != null) {
                if ($figure->getId() != $existFigure->getId()) {
                    $this->addFlash('warning', 'cette figure existe déjà !');
                    return $this->redirectToRoute('app_show_figure', ['slug' => $slug]);
                }
            }

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

            //add pictures
            $medias = $form->get('media')->getData();
            $oldMain = $mediaRepo->findOneBy(['main' => true, 'figure' => $figure->getId()]);
            $counter = 0;

            foreach ($medias as $media) {
                $fichier = md5(uniqid()) . '.' . $media->guessExtension();
                try {

                    $media->move(
                        $this->getParameter('figures_img_directory'),
                        $fichier
                    );
                } catch (FileException $e) {
                    //
                }

                $photo = new Media();
                if ($oldMain === null && $counter === 0) {
                    $photo->setMain(true);
                }
                $photo->setImage(true);
                $photo->setUrl($fichier);
                $figure->addMedium($photo);
            }
            // main selector

            $videos = $form->get('video')->getData();
            if ($videos != null) {

                $video = new Media();
                $video->setUrl($videos);
                $figure->addMedium($video);
            }


            if (!$figure->getId()) {
                $figure->setCreatedAt(new \DateTimeImmutable());
            } else {
                $figure->setUpdateAt(new \DateTimeImmutable());
            }
            $figure->setUser($user);

            if (!$figure->getId()) {
                $this->addFlash("success", "La figure a bien été ajouté");
            } else {
                $this->addFlash("success", "La modification de la figure a bien été prise en compte");
            }

            $em->persist($figure);
            $em->flush();
            $counter = $counter + 1;

            return $this->redirectToRoute('app_home');
        }
        return $this->render('figure/add_figure.html.twig', [
            'form' => $form->createView(),
            'figure' => $figure,
            'editMode' => $figure->getId() !== null

        ]);
    }

    #[Route('/figures/{slug}', name: 'app_show_figure')]
    public function showFigure(EntityManagerInterface $em, PaginatorInterface $paginator, UserRepository $user, Request $request, Figure $figure, Media $media, $slug): Response
    {
        $repo = $em->getRepository(Figure::class);
        $figure = $repo->findOneBy(['slug' => $slug]);
        $id = $figure->getId();
        $repo = $em->getRepository(Media::class);
        $media = $repo->findAll();
        $user = $this->getUser();


        $comment = new Comment();
        $commentForm = $this->createFormBuilder($comment)
            ->add('content')
            ->getForm();
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setFigure($figure);
            $comment->setUser($user);
            $comment->setCreatedAt(new \DateTimeImmutable());
            $em->persist($comment);
            $em->flush();
        }

        $repo = $em->getRepository(Comment::class);
        $comments = $repo->findBy(['figure' => $id], ['createdAt' => 'DESC']);
        $commentsAll = $paginator->paginate(
            $comments,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('figure/show_figure.html.twig', [
            'figure' => $figure,
            'media' => $media,
            'comments' => $commentsAll,
            'commentForm' => $commentForm->createView()
        ]);
    }
    #[Route('/media/supprimer/{id}', name: 'app_delete_media')]
    public function deleteMedia(Media $media, EntityManagerInterface $em, MediaRepository $mediaRepo,)
    {
        $slug = $media->getFigure()->getSlug();
        $image = $media->isImage();

        //récupérer l'url de l'image
        $url = $media->getUrl();
        if ($image != null) {
            //supprimer l'image
            unlink($this->getParameter('figures_img_directory') . '/' . $url);
        }

        $em->remove($media);
        $em->flush();

        // gestion mainMedia
        $figure = $media->getFigure();
        $oldMain = $mediaRepo->findOneBy(['main' => true, 'image' => true, 'figure' => $figure->getId()]);
        $newMain = $mediaRepo->findOneBy(['figure' => $figure->getId()]);
        dump($oldMain);
        dump($newMain);

        if ($oldMain === null) {
            $newMain->setMain(true);
            $em->persist($figure);
            $em->flush();
        }

        $this->addFlash('success', 'Le media a bien été supprimé');

        return $this->redirectToRoute('app_show_figure', [
            'slug' => $slug
        ]);
    }

    #[Route('/figures/supprimer/{id}', name: 'app_delete_figure')]
    public function deleteFigure(EntityManagerInterface $em, Figure $figure) //: Response
    {
        foreach ($figure->getMedia() as $media) {

            if ($media->isImage()) {

                $path = $this->getParameter('figures_img_directory') . '/' . $media->getUrl();

                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }

        $em->remove($figure);
        $em->flush();
        $this->addFlash('success', 'La figure a bien été supprimé');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/privee', name: 'app_private')]
    public function private(): Response
    {
        return $this->render('partials/private.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('partials/contact.html.twig');
    }
}
