<?php
namespace App\Command;

use App\Entity\Character;
use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImportCommand extends Command
{
    protected static $defaultName = 'starwars:import';
    protected $nbr = 30;
    protected $client;

    public function __construct(HttpClientInterface $client, EntityManagerInterface $em)
    {
        $this->client = $client;
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('starwars:import')
            ->setDescription('Import 30 characters from the Api swapi.dev ')
            ->setHelp('Sorry thers is no help :P');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $allContents = [];
        $output->writeln('==========Le chargement des character va commencer ===============!');
        $output->writeln('1 -- drop de base courante !!!');

        $command = $this->getApplication()->find('doctrine:schema:drop');

        $arguments = [
            '--force' => true,
        ];

        $greetInput = new ArrayInput($arguments);
        $returnCode = $command->run($greetInput, $output);
        $output->writeln('1 -- ' . $returnCode);

        $output->writeln('2 -- Mise a jour du schema !!!');

        $command = $this->getApplication()->find('doctrine:schema:update');

        $arguments = [
            '--force' => true,
        ];

        $greetInput = new ArrayInput($arguments);
        $returnCode = $command->run($greetInput, $output);
        $output->writeln('2 -- ' . $returnCode);

        for ($i = 1; $i <= $this->nbr; $i++) {
            $response = $this->client->request(
                'GET',
                "https://swapi.dev/api/people/$i/"
            );

            $statusCode = $response->getStatusCode();
            if ($statusCode == 200) {
                $contentType = $response->getHeaders()['content-type'][0];
                $content = $response->getContent();
                $content = $response->toArray();
                $char = new Character();
                $char->setName($content['name']);
                $char->setMass($content['mass']);
                $char->setHeight(floatval($content['height']));
                $char->setGender($content['gender']);
                $movies = $content['films'];
                $output->writeln('3 -- found  {' . count($movies) . '} movies !!');

                // Get all movies from this character
                foreach ($movies as $key => $value) {
                    $value = trim($value);
                    if ($value !== false && !empty($value)) {
                        $getMovieResponse = $this->client->request(
                            'GET',
                            $value
                        );

                        $code = $getMovieResponse->getStatusCode();
                        if ($code == 200) {
                            $contentTypeMovie = $getMovieResponse->getHeaders()['content-type'][0];
                            $contentMovie = $getMovieResponse->getContent();
                            $contentMovie = $getMovieResponse->toArray();
                            $movie = new Movie();
                            $movie->setName($contentMovie['title']);
                            $movie->setEpisode(intval($contentMovie['episode_id']));
                            $movie->setOpening($contentMovie['opening_crawl']);
                            $movie->setProducer($contentMovie['producer']);
                            $movie->setDirector($contentMovie['director']);
                            $movie->setReleaseDate($contentMovie['release_date']);
                            $this->em->persist($movie);
                            $char->addMovie($movie);

                        }
                    }

                }
                $this->em->persist($char);
                $output->writeln('Chargement du character [' . $content['name'] . "] réussi !");

            } else {
                $this->nbr++;
            }
        }

        $this->em->flush();
        return Command::SUCCESS;
    }

}
