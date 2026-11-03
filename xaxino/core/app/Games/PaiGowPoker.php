<?php

namespace App\Games;

use App\Constants\Status;

class PaiGowPoker extends Game {
    protected $alias                  = 'pai_gow_poker';
    protected $hasCustomCompleteLogic = true;
    protected $extraValidationRule    = [];

    /**
     * Deal player's 7 cards (store deck in session)
     *
     * @return array
     */
    protected function gameResult(): array {
        $deck = $this->initializeDeck();

        $rankName = [
            'royal_flush',
            'straight_flush',
            'four_of_a_kind',
            'full_house',
            'flush',
            'straight',
            'three_of_a_kind',
            'two_pair',
            'pair',
        ];

        $targetRank = $rankName[rand(0, 8)];
        $rankGet    = true;
        while ($rankGet) {
            $hand = $this->generatePokerHand($targetRank);
            $rank = $this->hasSpecificHand($hand);
            if ($rank != 'no_match') {
                $rankGet = false;
            }
        }

        $positions = array_rand($hand, 2);
        shuffle($deck);
        $nextTwoCard = array_slice(array_diff($deck, $hand), 0, 2);

        rsort($positions);
        foreach ($positions as $i => $pos) {
            array_splice($hand, $pos + 1, 0, [$nextTwoCard[$i]]);
        }
        session()->put('deck', $hand);
        $result['result'] = $hand;
        return $result;
    }

    private function generatePokerHand($targetRank) {
        $suits = ['H', 'D', 'C', 'S'];
        $ranks = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];

        $hand = [];

        switch ($targetRank) {
        case 'royal_flush':
            $suit = $suits[rand(0, 3)];
            $hand = ["A-$suit", "K-$suit", "Q-$suit", "J-$suit", "10-$suit"];
            break;

        case 'straight_flush':
            $suit       = $suits[rand(0, 3)];
            $startIndex = rand(0, 9);
            $hand       = [];
            for ($i = $startIndex; $i < $startIndex + 5; $i++) {
                $hand[] = $ranks[$i % 13] . "-$suit";
            }

            $rankMap = array_flip($ranks);

            usort($hand, function ($a, $b) use ($rankMap) {
                [$rankA] = explode('-', $a);
                [$rankB] = explode('-', $b);
                return $rankMap[$rankB] <=> $rankMap[$rankA];
            });

            break;

        case 'four_of_a_kind':
            $rank = $ranks[rand(0, 12)];
            $hand = [$rank . '-H', $rank . '-D', $rank . '-C', $rank . '-S', $ranks[rand(0, 12)] . '-H'];

            usort($hand, function ($a, $b) use ($rank) {
                return substr($a, 0, -2) === $rank ? -1 : 1;
            });
            break;

        case 'full_house':
            $rank1 = $ranks[rand(0, 12)];
            $rank2 = $ranks[rand(0, 12)];
            while ($rank2 == $rank1) {
                $rank2 = $ranks[rand(0, 12)];
            }
            $hand = [$rank1 . '-H', $rank1 . '-D', $rank1 . '-C', $rank2 . '-S', $rank2 . '-H'];

            usort($hand, function ($a, $b) use ($rank1, $rank2) {
                $rankA = array_search(substr($a, 0, -2), [$rank1, $rank2]);
                $rankB = array_search(substr($b, 0, -2), [$rank1, $rank2]);
                return $rankA - $rankB;
            });
            break;

        case 'flush':
            $suit      = $suits[array_rand($suits)];
            $hand      = [];
            $usedRanks = [];
            while (count($hand) < 5) {
                $rank = $ranks[array_rand($ranks)];
                if (!in_array($rank, $usedRanks)) {
                    $hand[]      = $rank . "-$suit";
                    $usedRanks[] = $rank;
                }
            }

            $rankMap = array_flip($ranks);

            usort($hand, function ($a, $b) use ($rankMap) {
                [$rankA] = explode('-', $a);
                [$rankB] = explode('-', $b);
                return $rankMap[$rankB] <=> $rankMap[$rankA];
            });

            break;

        case 'straight':
            $startIndex    = rand(0, 9);
            $hand          = [];
            $straightRanks = array_slice($ranks, $startIndex, 5);
            $usedSuits     = [];

            foreach ($straightRanks as $index => $rank) {

                if ($index === 0) {
                    $suit        = $suits[array_rand($suits)];
                    $usedSuits[] = $suit;
                } else {
                    $availableSuits = array_diff($suits, [$usedSuits[0]]);
                    $suit           = (count($usedSuits) === 1)
                    ? $availableSuits[array_rand($availableSuits)]
                    : $suits[array_rand($suits)];
                }

                $hand[]      = $rank . '-' . $suit;
                $usedSuits[] = $suit;
            }

            $rankMap = array_flip($ranks);

            usort($hand, function ($a, $b) use ($rankMap) {
                [$rankA] = explode('-', $a);
                [$rankB] = explode('-', $b);
                return $rankMap[$rankB] <=> $rankMap[$rankA];
            });

            break;

        case 'three_of_a_kind':
            $rank = $ranks[rand(0, 12)];
            $hand = [$rank . '-H', $rank . '-D', $rank . '-C', $ranks[rand(0, 12)] . '-S', $ranks[rand(0, 12)] . '-H'];
            usort($hand, function ($a, $b) use ($rank) {
                if (substr($a, 0, -2) === $rank) {
                    return -1;
                } else if (substr($b, 0, -2) === $rank) {
                    return 1;
                } else {
                    return 0;
                }
            });
            break;

        case 'two_pair':
            $rank1 = $ranks[rand(0, 12)];
            $rank2 = $ranks[rand(0, 12)];
            while ($rank2 == $rank1) {
                $rank2 = $ranks[rand(0, 12)];
            }
            $hand = [$rank1 . '-H', $rank1 . '-D', $rank2 . '-C', $rank2 . '-S', $ranks[rand(0, 12)] . '-H'];
            usort($hand, function ($a, $b) use ($rank1, $rank2) {
                $rankA = array_search(substr($a, 0, -2), [$rank1, $rank2]);
                $rankB = array_search(substr($b, 0, -2), [$rank1, $rank2]);
                return $rankA - $rankB;
            });
            break;

        case 'pair':
            $rank = $ranks[rand(0, 12)];
            $hand = [$rank . '-H', $rank . '-D', $ranks[rand(0, 12)] . '-C', $ranks[rand(0, 12)] . '-S', $ranks[rand(0, 12)] . '-H'];
            usort($hand, function ($a, $b) use ($rank) {
                if (substr($a, 0, -2) === $rank) {
                    return -1;
                } else if (substr($b, 0, -2) === $rank) {
                    return 1;
                } else {
                    return 0;
                }
            });
            break;

        case 'high_card':
            for ($i = 0; $i < 5; $i++) {
                $hand[] = $ranks[rand(0, 12)] . '-' . $suits[rand(0, 3)];
            }
            usort($hand, function ($a, $b) use ($ranks) {
                $rankA = array_search(substr($a, 0, -2), $ranks);
                $rankB = array_search(substr($b, 0, -2), $ranks);
                return $rankB - $rankA;
            });
            break;

        default:
            break;
        }

        return $hand;
    }

    private function hasSpecificHand($hand) {
        $handTypes = [
            'royal_flush',
            'straight_flush',
            'four_of_a_kind',
            'full_house',
            'flush',
            'straight',
            'three_of_a_kind',
            'two_pair',
            'pair',
            'high_card',
        ];

        foreach ($handTypes as $handType) {
            $methodName = 'is' . str_replace('_', '', ucwords($handType, '_'));
            if ($this->$methodName($hand)) {
                return $handType;
            }
        }
        return 'no_match';
    }

    private function initializeDeck() {
        $suits = ['H', 'D', 'C', 'S'];
        $ranks = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        $deck  = [];
        foreach ($suits as $suit) {
            foreach ($ranks as $rank) {
                $deck[] = $rank . '-' . $suit;
            }
        }
        shuffle($deck);
        return $deck;
    }

    public function customCompleteLogic($gameLog) {
        $playerCards = json_decode($gameLog->result);
        $error       = false;

        $lowHandCards = gettype($this->request->low_hand) == 'array' ? $this->request->low_hand : json_decode($this->request->low_hand, true);

        foreach ($lowHandCards as $key => $lowCard) {
            if (!in_array($lowCard, $playerCards)) {
                $error = true;
                break;
            }
        }

        $highHandCards = gettype($this->request->high_hand) == 'array' ? $this->request->high_hand : json_decode($this->request->high_hand, true);

        foreach ($highHandCards as $key => $highCard) {
            if (!in_array($highCard, $playerCards)) {
                $error = true;
                break;
            }
        }
        
        if ($error) {
            return [
                'data' => [
                    'error'   => true,
                    'message' => 'Invalid card selection',
                ],
            ];
        }

        $probableWin = $this->demoPlay ? $this->game->probable_win_demo : $this->game->probable_win;
        $random      = mt_rand(0, 100);

        if ($random <= $probableWin) {
            $win          = Status::WIN;
            $userCardRank = $this->hasSpecificHand($highHandCards);

            $rankName = [
                'royal_flush',
                'straight_flush',
                'four_of_a_kind',
                'full_house',
                'flush',
                'straight',
                'three_of_a_kind',
                'two_pair',
                'pair',
                'high_card',
            ];

            $key = array_search($userCardRank, $rankName, true);
            if ($key) {
                $arrayPosition = $key + 1;
            } else {
                $arrayPosition = 9;
            }

            $targetRank = $rankName[$arrayPosition];
            $rankGet    = true;
            while ($rankGet) {
                $hand = $this->generatePokerHand($targetRank);
                $rank = $this->hasSpecificHand($hand);
                if ($rank != 'no_match') {
                    $rankGet = false;
                }
            }

            $dealerFirstHands  = $hand;
            $dealerSecondHands = $this->getNextTwoLowCards($this->initializeDeck(), $lowHandCards);
        } else {
            $win          = Status::LOSS;
            $userCardRank = $this->hasSpecificHand($highHandCards);

            $rankName = [
                'royal_flush',
                'straight_flush',
                'four_of_a_kind',
                'full_house',
                'flush',
                'straight',
                'three_of_a_kind',
                'two_pair',
                'pair',
                'high_card',
            ];

            $key = array_search($userCardRank, $rankName, true);
            if ($key) {
                $arrayPosition = $key - 1;
            } else {
                $arrayPosition = 9;
            }

            $targetRank = $rankName[$arrayPosition];
            $rankGet    = true;
            while ($rankGet) {
                $hand = $this->generatePokerHand($targetRank);
                $rank = $this->hasSpecificHand($hand);
                if ($rank != 'no_match') {
                    $rankGet = false;
                }
            }

            $dealerFirstHands  = $hand;
            $dealerSecondHands = $this->getNextTwoBigCards($this->initializeDeck(), $lowHandCards);
        }

        $playerCards = [
            'high' => $highHandCards,
            'low'  => $lowHandCards,
        ];

        $dealerHands = [
            'high' => $dealerFirstHands,
            'low'  => $dealerSecondHands,
        ];

        $gameLog->user_select = 'High hand: ' . json_encode($playerCards['high']) . ' | Low hand: ' . json_encode($playerCards['low']);
        $gameLog->result      = json_encode(array_merge($dealerFirstHands, $dealerSecondHands));
        $gameLog->win_status  = $win;
        $gameLog->save();

        return [
            'should_return' => false,
            'data'          => [
                'userCardRank' => $userCardRank,
                'rank'         => $rank,
                'result'       => $win,
                'dealer'       => $dealerHands,
            ],
        ];
    }

    function getNextTwoBigCards(array $deck, array $givenCards): array {
        $rankOrder = [
            '2'  => 2, '3'  => 3, '4'  => 4, '5'  => 5,
            '6'  => 6, '7'  => 7, '8'  => 8, '9'  => 9,
            '10' => 10, 'J' => 11, 'Q' => 12, 'K' => 13, 'A' => 14,
        ];

        $remainingCards = array_values(array_diff($deck, $givenCards));

        usort($remainingCards, function ($a, $b) use ($rankOrder) {
            [$rankA] = explode('-', $a);
            [$rankB] = explode('-', $b);

            return $rankOrder[$rankB] <=> $rankOrder[$rankA];
        });

        $nextTwoBig = array_slice($remainingCards, 0, 2);
        return $nextTwoBig;
    }

    function getNextTwoLowCards(array $deck, array $givenCards): array {
        $rankOrder = [
            '2'  => 2, '3'  => 3, '4'  => 4, '5'  => 5,
            '6'  => 6, '7'  => 7, '8'  => 8, '9'  => 9,
            '10' => 10, 'J' => 11, 'Q' => 12, 'K' => 13, 'A' => 14,
        ];

        $remainingCards = array_values(array_diff($deck, $givenCards));

        usort($remainingCards, function ($a, $b) use ($rankOrder) {
            [$rankA] = explode('-', $a);
            [$rankB] = explode('-', $b);

            return $rankOrder[$rankA] <=> $rankOrder[$rankB];
        });

        $lowTwo = array_slice($remainingCards, 0, 2);

        return $lowTwo;
    }

    public function adjustDealerHand(array $playerCards, bool $forceWin): array {

        $deck = $this->initializeDeck();
        $deck = array_values(array_filter($deck, fn($c) => !in_array($c, $playerCards, true)));
        usort($deck, fn($a, $b) => $this->cardRankValue($a) <=> $this->cardRankValue($b));

        if ($forceWin) {
            $strongCards = array_slice($deck, 0, 20);
            shuffle($strongCards);
            $dealerCards = array_slice($strongCards, 0, 7);
        } else {
            $weakCards = array_slice($deck, -20);
            shuffle($weakCards);
            $dealerCards = array_slice($weakCards, 0, 7);
        }
        return $this->autoArrangeHands($dealerCards);
    }

    protected function cardRankValue(string $card): int {
        $rankOrder = [
            '2'   => 2,
            '3'   => 3,
            '4'   => 4,
            '5'   => 5,
            '6'   => 6,
            '7'   => 7,
            '8'   => 8,
            '9'   => 9,
            '10'  => 10,
            'J'   => 11,
            'Q'   => 12,
            'K'   => 13,
            'A'   => 14,
            'J-B' => 15,
        ];

        if ($card === 'J-B') {
            return $rankOrder['J-B'];
        }

        $parts = explode('-', $card);
        $rank  = $parts[0] ?? $card;

        return $rankOrder[$rank] ?? 0;
    }

    public function handValue(array $hand): int {
        $sum = 0;
        foreach ($hand as $c) {
            $sum += $this->cardRankValue($c);
        }
        return $sum;
    }

    public function autoArrangeHands(array $cards): array {

        $cards = array_values($cards);

        usort($cards, fn($a, $b) => $this->cardRankValue($b) <=> $this->cardRankValue($a));

        $high = array_slice($cards, 0, 5);
        $low  = array_slice($cards, 5, 2);

        return ['high' => $high, 'low' => $low];
    }

    public function compareHands(array $player, array $dealer): string {
        $highWin = $this->handValue($player['high']) > $this->handValue($dealer['high']);
        $lowWin  = $this->handValue($player['low']) > $this->handValue($dealer['low']);

        if ($highWin && $lowWin) {
            return Status::WIN;
        }

        if (!$highWin && !$lowWin) {
            return Status::LOSS;
        }

        return Status::PUSH;
    }

    public function isRoyalFlush($hand) {
        $requiredRanks = ['10', 'J', 'Q', 'K', 'A'];
        $requiredSuits = array_unique(array_map(function ($card) {
            return explode('-', $card)[1];
        }, $hand));

        return count(array_intersect($requiredRanks, $this->getRanks($hand))) === 5
        && count($requiredSuits) === 1;
    }

    private function getRanks($hand) {
        return array_map(function ($card) {
            return explode('-', $card)[0];
        }, $hand);
    }

    public function isStraightFlush($hand) {
        $ranks = $this->getRanks($hand);
        $suit  = explode('-', $hand[0])[1];

        return count($ranks) === 5
        && count(array_diff($ranks, array_values(range(min($ranks), max($ranks))))) === 0
        && count(array_unique(array_map(function ($card) {
            return explode('-', $card)[1];
        }, $hand))) === 1;
    }

    public function isFourOfAKind($hand) {
        $rankCount = array_count_values($this->getRanks($hand));
        return in_array(4, $rankCount);
    }

    public function isFullHouse($hand) {
        $rankCount = array_count_values($this->getRanks($hand));
        return in_array(3, $rankCount) && in_array(2, $rankCount);
    }

    public function isFlush($hand) {
        $suits = array_map(function ($card) {
            return explode('-', $card)[1];
        }, $hand);

        return count(array_unique($suits)) === 1;
    }

    public function isStraight($hand) {
        $ranks = $this->getRanks($hand);
        return count($ranks) === 5
        && count(array_diff($ranks, array_values(range(min($ranks), max($ranks))))) === 0
        && count(array_unique($ranks)) === 5;
    }

    public function isThreeOfAKind($hand) {
        $rankCount = array_count_values($this->getRanks($hand));
        return in_array(3, $rankCount);
    }

    public function isTwoPair($hand) {
        $rankCount = array_count_values($this->getRanks($hand));
        return count(array_filter($rankCount, function ($count) {
            return $count === 2;
        })) === 2;
    }

    public function isPair($hand) {
        $rankCount = array_count_values($this->getRanks($hand));
        return in_array(2, $rankCount);
    }

    public function isHighCard($hand) {
        $ranks = $this->getRanks($hand);

        return count($ranks) === 5
        && count(array_diff($ranks, array_values(range(min($ranks), max($ranks))))) === 0
        && count(array_unique($ranks)) === 5;
    }
}
