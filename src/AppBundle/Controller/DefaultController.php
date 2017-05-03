<?php

namespace AppBundle\Controller;

use AppBundle\Service\NeuralNetwork;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/save-image", name="save_image")
     * @Method("POST")
     * @param Request $request
     * @return RedirectResponse
     */
    public function saveImages(Request $request)
    {
        if ($request->request->get('smiley')) {
            $class = $request->request->get('submit');
            $filename = md5(uniqid() . time()) . '.' . $class . '.png';

            $image = str_replace('data:image/png;base64,', '', $request->request->get('smiley'));
            $image = str_replace(' ', '+', $image);
            $image = base64_decode($image);

            if ($class == 'unknown') {
                file_put_contents($this->get('neural_network')->testDir . $filename, $image);
                return new RedirectResponse($this->generateUrl('decide', ['image' => $filename]));
            } else {
                file_put_contents($this->get('neural_network')->trainDir . $filename, $image);
                $this->addFlash('success', 'Dein Bild wurde zum Trainingsset "' . ($class == 'smile' ? 'fröhlich' : 'traurig') . '" hinzugefügt.');
                return new RedirectResponse($this->generateUrl('homepage'));
            }
        }

        $this->addFlash('error', 'Du hast nichts gemalt...');

        return new RedirectResponse($this->generateUrl('homepage'));
    }

    /**
     * @Route("/decide/{image}", name="decide")
     *
     * @param $image
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function decideAction($image)
    {
        $brain = $this->get('neural_network')->createBrain('smiley');

        $input = NeuralNetwork::getImagePixels($this->get('neural_network')->testDir . '/' . $image);
        $decision = $brain->thinkAbout($input);

        return $this->render('default/decision.html.twig', [
            'image' => $image,
            'decision' => $decision
        ]);
    }

    /**
     * @Route("/add-to-training/{image}/{class}", name="add_to_training")
     *
     * @param $image
     * @param $class
     * @return RedirectResponse
     */
    public function addToTraining($image, $class)
    {
        $fs = new Filesystem();

        $testDir = $this->get('neural_network')->testDir . '/';
        $trainDir = $this->get('neural_network')->trainDir . '/';

        if ($fs->exists($testDir . $image) && in_array($class, ['smile', 'sad'])) {
            $fs->copy($testDir . $image, $trainDir . str_replace('unknown', $class, $image));
        }

        $this->addFlash('success', 'Dein Bild wurde zum Trainingsset "' . ($class == 'smile' ? 'fröhlich' : 'traurig') . '" hinzugefügt.');

        return new RedirectResponse($this->generateUrl('homepage'));
    }
}
