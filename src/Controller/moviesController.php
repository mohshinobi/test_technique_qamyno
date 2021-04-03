<?php
namespace App\Controller;

use App\Entity\Movie;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class moviesController extends AbstractController
{

    /**
     *
     * @Route("/movies/", name="movies")
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();
        $movies = $em->getRepository(Movie::class)->findAll();
        return $this->render('movies.html.twig', [
            'movies' => $movies,
        ]);
    }
    /**
     *
     * @Route("/movie/{id}", name="show_movie")
     */
    public function movie(Movie $movie)
    {
        dump($movie);
        return $this->render('singleMovie.html.twig', [
            'movie' => $movie,
        ]);
    }

}
