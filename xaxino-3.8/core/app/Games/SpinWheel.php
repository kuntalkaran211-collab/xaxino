<?php

namespace App\Games;

use App\Constants\Status;

class SpinWheel extends Game
{
    protected $alias = 'spin_wheel';
    protected $resultShowOnStart = true;
    protected $hasCustomCompleteLogic = true;
    protected $extraValidationRule = [
        'choose' => 'required|in:red,blue'
    ];

    protected function gameResult()
    {
        $probableWin = $this->demoPlay
            ? $this->game->probable_win_demo
            : $this->game->probable_win;

        $random = mt_rand(0, 100);
        if ($random <= $probableWin) {
            $winLossData['win_status'] = Status::WIN;
            $winLossData['result'] = ($this->request->choose == 'blue') ? 'BLUE' : 'RED';
        } else {
            $winLossData['win_status'] = Status::LOSS;
            $winLossData['result'] = ($this->request->choose == 'blue') ? 'RED' : 'BLUE';
        }
        return $winLossData;
    }

    protected function customCompleteLogic($gameLog)
    {
        $res['win_status'] = $gameLog->win_status;
        $this->extraResponseOnEnd = array_merge($this->extraResponseOnEnd, $res);
        return ['should_return' => false];
    }
}
