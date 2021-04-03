<?php
namespace App\Controller;

use App\Entity\Character;
use App\Form\Type\CharacterType;
use App\Form\Type\SearchCharacterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class indexController extends AbstractController
{

    private $apiKey;
    private $client;
    private $nbr = 30;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @Route("/" , name="search")
     */
    public function index(Request $request)
    {

        $form = $this->createForm(SearchCharacterType::class);
        $contents = null;
        $em = $this->getDoctrine()->getManager();
        $char = $em->getRepository(Character::class)->findAll();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $search = trim($form->get('search')->getData());

                if ($search && !empty($search)) {
                    $em = $this->getDoctrine()->getManager();
                    $char = $em->getRepository(Character::class)->findLike($search);
                    dump($char);

                }

            }

        }
        return $this->render('index.html.twig', [
            'char' => $char,
            'form' => $form->createView(),
        ]);

    }

    /**
     * @Route("/character/{id}" , name="character")
     */
    public function showCharacter(Character $char, Request $request, SluggerInterface $slugger)
    {
        $form = $this->createForm(CharacterType::class, $char);
        $em = $this->getDoctrine()->getManager();

        if ($request->isMethod('POST')) {

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $picture = $form->get('picture')->getData();
                if ($picture !== null) {
                    $originalFilename = pathinfo($picture->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $picture->guessExtension();
                    try {
                        $picture->move($this->getParameter('avatars_directory'), $newFilename);
                    } catch (FileException $e) {
                        $msg = ['code' => 'error', 'title' => 'Erreur', 'content' => 'Error uploading image / erreur dans l\'upload de l\'avatar \n' . $e];
                    }
                    $char->setPicture("/uploads/avatars/" . $newFilename);
                } else {
                    $char->setPicture($char->getPicture());
                }

            }
            $em->persist($char);
            $em->flush();

            $this->addFlash(
                'success',
                'Mise a jour réussite!'
            );

        }

        return $this->render('character.html.twig', [
            'char' => $char,
            'form' => $form->createView(),
        ]);

    }

    /**
     * @Route("/delete/character/{id}" ,name="delete")
     */

    public function delete(Character $char)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($char);
            $em->flush();

        } catch (\Throwable $th) {
            $this->addFlash(
                'danger',
                'Erreur dans la suppression du character [' . $char->getName() . ']'
            );

        }
        $this->addFlash(
            'warning',
            'Suppression réussite du character [' . $char->getName() . ']'
        );

        return $this->redirectToRoute("search");
    }

}
