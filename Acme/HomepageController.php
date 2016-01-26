<?php

namespace Acme;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class HomepageController
{
    /**
     * @var array
     */


    public function showIndex(Request $request, Application $app)
    {

        $ranking = Games::getRanking();
        return $app['twig']->render('page/homepage.twig', array(
            "ranking" => $ranking
        ));
    }
    public function showPlayer(Request $request, Application $app, $player)
    {

        $profile = Games::getPlayer($player);
        return $app['twig']->render('page/player.twig', array(
            "profile" => $profile
        ));
    }

    public function showForm(Request $request, Application $app)
    {
        $game = new Games();
        $form = $game->createForm($app);
        $form->handleRequest($request);

        if($form->isValid())
        {
            $game->store();
            return $app->redirect('/');
        }


        return $app['twig']->render('page/form.twig', array(
            "form" => $form->createView()
        ));
    }

}
