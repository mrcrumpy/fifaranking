<?php
namespace Acme;

use RedBean_Facade as R;
use Symfony\Component\Validator\Constraints as Assert;

class Games
{
    /**
    * @param \Silex\Application $app
    * @param array $events
    * @param string|null $slug
    * @return \Symfony\Component\Form\Form
    */

    public $player;
    public $enemy;
    public $goals;
    public $goals_against;

    static function getRanking()
    {
        return R::getAll('SELECT
          name,
          SUM(points) points,
          SUM(goals) goals,
          SUM(goals_against) goals_against,
          count(points) count,
          count(case points when 3 then 1 else null end) victories,
          count(case points when 1 then 1 else null end) draw,
          count(case points when 0 then 1 else null end) loss
          FROM games g INNER JOIN user u ON g.player_id = u.id GROUP BY u.id ORDER BY SUM(g.points) DESC');
    }

    static function getLastGames()
    {
        return R::getAll('SELECT
          g.id id, u.name player, goals, goals_against, c.name enemy
          FROM games g INNER JOIN user u ON g.player_id = u.id INNER JOIN user c ON g.enemy_id = c.id WHERE (g.id % 2) = 0 ORDER BY g.date DESC LIMIT 5');
    }

    static function getPlayer($player)
    {
        return R::getAll('SELECT name, SUM(points) points, SUM(goals) goals, SUM(goals_against) goals_against, count(case points when 3 then 1 else null end) victories, count(case points when 1 then 1 else null end) draw, count(case points when 0 then 1 else null end) loss FROM games g INNER JOIN user u ON g.player_id = u.id WHERE u.name = ? GROUP BY u.id ORDER BY g.points DESC', [$player]);
    }
    static function getGame($game)
    {
        return R::getAll('SELECT
          g.id id, u.name player, goals, goals_against, c.name enemy
          FROM games g INNER JOIN user u ON g.player_id = u.id INNER JOIN user c ON g.enemy_id = c.id WHERE g.id = ?', [$game]);
    }

    static function getUserID($name)
    {
        $user = R::getAll('SELECT id FROM user WHERE name = ?', [$name]);
        return $user[0][id];
    }

    static function getPlayerNames()
    {
        $names = R::getAssoc('SELECT name FROM user');
        return $names;
    }

    static function getPoints($goals, $goals_against)
    {
        if($goals > $goals_against){
            return 3;
        }
        elseif($goals_against > $goals){
            return 0;
        }
        else{
            return 1;
        }
    }

    public function createForm(\Silex\Application $app) {
        $formBuilder = $app['form.factory']->createNamedBuilder('game', 'form', $this);

        $form = $formBuilder
            ->add('player', 'choice', array(
                'choices'     => self::getPlayerNames(),
                'multiple'    => false,
                'placeholder' => 'Spieler wählen ...',
                'constraints' => new Assert\NotBlank()
            ))
            ->add('enemy', 'choice', array(
                'choices'     => self::getPlayerNames(),
                'multiple'    => false,
                'placeholder' => 'Spieler wählen ...',
                'constraints' => new Assert\NotBlank()
            ))
            ->add('goals', 'number', array('constraints' => new Assert\NotBlank()))
            ->add('goals_against', 'number', array('constraints' => new Assert\NotBlank()))
            ->getForm();

        return $form;
    }

    public function store()
    {
        date_default_timezone_set('Europe/Berlin');
        $date = date("Y-m-d h:i:s", time());

        $game = R::dispense('games');
        $game->player_id = self::getUserID($this->player);
        $game->enemy_id = self::getUserID($this->enemy);
        $game->date = $date;
        $game->goals = $this->goals;
        $game->goals_against = $this->goals_against;
        $game->points = self::getPoints($this->goals, $this->goals_against);
        R::store($game);

        $game = R::dispense('games');
        $game->player_id = self::getUserID($this->enemy);
        $game->enemy_id = self::getUserID($this->player);
        $game->date = $date;
        $game->goals = $this->goals_against;
        $game->goals_against = $this->goals;
        $game->points = self::getPoints($this->goals_against, $this->goals);
        return R::store($game);
    }

}