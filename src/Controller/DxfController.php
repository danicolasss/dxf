<?php

namespace App\Controller;

use DXFighter\DXFighter;
use DXFighter\lib\Circle;
use DXFighter\lib\Line;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DxfController extends AbstractController
{
    #[Route('/dxf', name: 'dxf_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('dxf/index.html.twig');
    }

    #[Route('/dxf/generate', name: 'dxf_generate', methods: ['POST'])]
    public function generate(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $squareWidth = (float) ($data['squareWidth'] ?? 100);
        $squareHeight = (float) ($data['squareHeight'] ?? 100);
        $circles = $data['circles'] ?? [];
        $cutLines = $data['cutLines'] ?? [];

        $fighter = new DXFighter();

        // Carré (4 lignes)
        $fighter->addEntity(new Line([0, 0, 0], [$squareWidth, 0, 0]));
        $fighter->addEntity(new Line([$squareWidth, 0, 0], [$squareWidth, $squareHeight, 0]));
        $fighter->addEntity(new Line([$squareWidth, $squareHeight, 0], [0, $squareHeight, 0]));
        $fighter->addEntity(new Line([0, $squareHeight, 0], [0, 0, 0]));

        // Cercles
        foreach ($circles as $circle) {
            $x = (float) ($circle['x'] ?? 0);
            $y = (float) ($circle['y'] ?? 0);
            $radius = (float) ($circle['radius'] ?? 10);
            $fighter->addEntity(new Circle([$x, $y, 0], $radius));
        }

        // Lignes de coupe entre cercles
        foreach ($cutLines as $line) {
            $x1 = (float) ($line['x1'] ?? 0);
            $y1 = (float) ($line['y1'] ?? 0);
            $x2 = (float) ($line['x2'] ?? 0);
            $y2 = (float) ($line['y2'] ?? 0);
            $fighter->addEntity(new Line([$x1, $y1, 0], [$x2, $y2, 0]));
        }

        // Capturer la sortie pour éviter le bug "headers already sent"
        ob_start();
        $dxfContent = $fighter->toString(false);
        $extraOutput = ob_get_clean();

        // Si toString() a echo du contenu au lieu de le retourner
        if (empty($dxfContent) && !empty($extraOutput)) {
            $dxfContent = $extraOutput;
        }

        return new Response($dxfContent, 200, [
            'Content-Type' => 'application/dxf',
            'Content-Disposition' => 'attachment; filename="dessin.dxf"',
        ]);
    }
}


