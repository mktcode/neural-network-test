<?php

namespace AppBundle\Controller;

use AppBundle\Service\Brain;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        $trainingSet = [];

        $finder = new Finder();
        $finder->files()->name('*.png')->in($this->get('brain')->trainDir);
        $finder->sort(function ($a, $b) {
            /** @var \SplFileInfo $a */
            /** @var \SplFileInfo $b */
            return $b->getMTime() - $a->getMTime();
        });

        foreach ($finder as $file) {
            $smile = (bool) substr_count($file->getRealPath(), '.smile.');
            $trainingSet[] = [$file->getRelativePathname(), (int) $smile];
        }

        return $this->render('default/index.html.twig', [
            'trainingSet' => $trainingSet
        ]);
    }

    /**
     * @Route("/save-image", name="save_image")
     * @Method("POST")
     * @param Request $request
     * @return RedirectResponse
     */
    public function saveImageAction(Request $request)
    {
        $smiley = $request->request->get('smiley');

        if ($smiley) {
            $class = $request->request->get('submit');
            $filename = md5(uniqid() . time()) . '.' . $class . '.png';

            $image = str_replace('data:image/png;base64,', '', $smiley);
            $image = str_replace(' ', '+', $image);
            $image = base64_decode($image);

            $brain = $this->get('brain');

            if ($class == 'unknown') {
                file_put_contents($brain->testDir . '/' . $filename, $image);
                return new RedirectResponse($this->generateUrl('decide', ['image' => $filename, 'dir' => 'test']));
            } elseif (in_array($class, ['smile', 'sad'])) {
                file_put_contents($brain->trainDir . '/' . $filename, $image);
                return new RedirectResponse($this->generateUrl('train'));
            }
        }

        $this->addFlash('error', 'No smiley transmitted. :(');

        return new RedirectResponse($this->generateUrl('homepage'));
    }

    /**
     * @Route("/decide/{dir}/{image}", name="decide")
     *
     * @param $image
     * @param $dir
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function decideAction($image, $dir)
    {
        $brain = $this->get('brain');
        $brain->create('smiley');
        $decision = $brain->think(self::getImagePixels($brain->dataDir . '/' . $dir . '/' . $image));

        $finder = new Finder();
        $finder->files()->name('*.png')->in($brain->trainDir);
        $numberOfTrainingSets = $finder->count();

        return $this->render('default/decision.html.twig', [
            'image' => $image,
            'dir' => $dir,
            'decision' => $decision,
            'numberOfTrainingSets' => $numberOfTrainingSets
        ]);
    }

    /**
     * @Route("/train", name="train")
     */
    public function trainAction()
    {
        $brain = $this->get('brain');
        $brain->create('smiley');

        $data = [];
        foreach (glob($brain->trainDir . '/*.png') as $key => $path) {
            $smile = (bool) substr_count($path, '.smile.');
            $data[] = [self::getImagePixels($path), [(int) $smile]];
        }

        $brain->createTraining($data);
        $brain->train();
        $brain->save();

        $this->addFlash('success', 'AI says: Thank you! Learning never stops... :)');

        return new RedirectResponse($this->generateUrl('homepage'));
    }

    /**
     * @Route("/add-to-training/{image}/{class}", name="add_to_training")
     *
     * @param $image
     * @param $class
     * @return RedirectResponse
     */
    public function addToTrainingAction($image, $class)
    {
        $fs = new Filesystem();
        $brain = $this->get('brain');

        // copy image and train
        $testImagePath = $brain->testDir . '/' . $image;
        if ($fs->exists($testImagePath) && in_array($class, ['smile', 'sad'])) {
            $fs->copy($testImagePath, $brain->trainDir . '/' . str_replace('unknown', $class, $image));
            $fs->remove($testImagePath);

            return new RedirectResponse($this->generateUrl('train'));
        }

        return new RedirectResponse($this->generateUrl('homepage'));
    }

    static function getImagePixels($imagePath)
    {
        $pixelColors = [];
        $image = imagecreatefrompng($imagePath);

        if ($image) {
            $imageWidth = imagesx($image);
            $imageHeight = imagesy($image);

            for ($y = 0; $y < $imageHeight; $y++) {
                for ($x = 0; $x < $imageWidth; $x++) {
                    $pixelColors[] = imagecolorat($image, $x, $y) ? 0 : 1;
                }
            }
        }

        return $pixelColors;
    }
}
