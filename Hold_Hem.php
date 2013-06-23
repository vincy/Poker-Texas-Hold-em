<?php
class Hold_Hem
{
    // Points values
    const HighCard = 1;
    const Pair = 2;
    const DoublePair = 3;
    const ThreeOfAKind = 4;
    const Straight = 5;
    const Flush = 6;
    const FullHouse = 7;
    const Poker = 8;
    const StraightFlush = 9;
    
    public function __construct($players)
    {
      $this->createDeck()->shuffle()->getHoleCards($players)->getShowdown()->evaluateHands()->getWinners();
    }
    
    public function get()
    {
       $array = array(
            'player_cards' => $this->hole_cards,
            'showdown' => $this->showdown,
            'winning_hands' => $this->winning_hands,
            'winners' => $this->winners
        );
        return $array;
    }

    public function createDeck()
    {
        $numbers = array('A', 2, 3, 4, 5, 6, 7, 8, 9, 'T', 'J', 'Q', 'K');
        $suites = array('h', 'd', 'c', 's');
        foreach($suites as $suit)
            foreach($numbers as $number)
                $this->deck[] = $number.$suit;
        return $this;
    }

    public function shuffle()
    {
        $times = 3;
        while($times--)
            shuffle($this->deck);
        return $this;
    }

    public function getHoleCards($players)
    {
        $this->hole_cards = array();
        $times = 2;
        while($times--)
            for($i=1;$i<=$players;$i++)
                $this->hole_cards[$i][] = array_shift($this->deck);
        return $this;
    }

    public function getShowdown()
    {
        $this->fold = array();
        $this->showdown = array();
        $array_fold = array(1,5,7);
        for($i=1;$i<=8;$i++)
        {
            $card = array_shift($this->deck);
            if(in_array($i, $array_fold))
                $this->fold[] = $card;
            else
                $this->showdown[] = $card;
        }
        return $this;
    }

    protected function decryptHand($hand)
    {
        $to_change = array('T', 'J', 'Q', 'K', 'A');
        $change = array(10, 11, 12, 13, 14);
        foreach($hand as &$card)
        {
            foreach($to_change as $key => $value)
                $card = str_replace($value, $change[$key], $card);
        }
        return $hand;
    }

    protected function cryptHand($hand)
    {
        $to_change = array(10, 11, 12, 13, 14);
        $change = array('T', 'J', 'Q', 'K', 'A');
        foreach($hand as &$card)
        {
           if((int)$card == 1)
           {
              $card = str_replace(1, "A", $card);
              continue;
           }
           foreach($to_change as $key => $value)
                $card = str_replace($value, $change[$key], $card);
        }
        return $hand;
    }

    protected function onlyNumbers($hand)
    {
        foreach($hand as &$card)
            $card = substr($card, 0, -1);
        return $hand;
    }

    protected function onlySuits($hand)
    {
        foreach($hand as &$card)
            $card = substr($card, -1);
        return $hand;
    }

    public function evaluateHand($hand)
    {
        $hand = $this->decryptHand($hand);
        $points = array(
            'StraightFlush',
            'Poker',
            'FullHouse',
            'Straight',
            'ThreeOfAKind',
            'DoublePair',
            'Pair',
            'HighCard'
        );
        foreach($points as $point)
        {
           $point_method = "is".$point;
           if($hand_value = $this->$point_method($hand))
              return array(
                'point' => constant('self::'.$point),
                'printable_point' => $point,
                'hand_value' => $hand_value
              );
        }
    }

    // Points returns an array 'value' & 'hand'
    
    public function isHighCard($hand)
    {
        // it's highcard mandatory
        rsort($hand, SORT_NUMERIC);
        $best_hand = $hand;
        $number_best_hand = $this->onlyNumbers($best_hand);
        return array(
            'value' => array_sum($number_best_hand),
            'best_hand' => array_slice($hand, 0, 5)
        );
    }

    public function isPair($hand)
    {
        $numbers = $this->onlyNumbers($hand);
        $numbers_count = array_count_values($numbers);
        krsort($numbers_count);
        if($search_number = array_search(2, $numbers_count))
        {
            $best_hand = array();
            foreach($hand as $key => $card)
            {
                if($search_number == (int)$card)
                {
                    $best_hand[] = $card;
                    unset($hand[$key]);
                }
            }
            rsort($hand, SORT_NUMERIC);
            $hand_merge = array_slice($hand, 0, 3);
            return array(
                'value' => $search_number.".".array_sum($this->onlyNumbers($hand_merge)),
                'best_hand' =>  array_merge($best_hand, $hand_merge)
               );
        }
        return false;
    }
    
    public function isDoublePair($hand)
    {
        $numbers = $this->onlyNumbers($hand);
        $numbers_count = array_count_values($numbers);
        krsort($numbers_count);
        foreach($numbers_count as $number => $count)
            if($count == 2)
               $double_pair[] = $number;
        if(count($double_pair) >= 2) // TRICK return first_pair.second_pair
        {
           if(count($double_pair) == 3)   unset($double_pair[2]);
           $best_hand = array();
           foreach($hand as $key => $card)
           {
              if(in_array((int)$card, $double_pair))
              {
                 $best_hand[] = $card;
                 unset($hand[$key]);
              }
           }
           rsort($hand, SORT_NUMERIC);
           return array(
               'value' => implode(".", array_merge($double_pair, array((int)($hand[0])))),
               'best_hand' => array_merge($best_hand, array($hand[0]))
           );
        }
        return false;
    }
    
    public function isThreeOfAKind($hand)
    {
        $numbers = $this->onlyNumbers($hand);
        $numbers_count = array_count_values($numbers);
        krsort($numbers_count);
        if($search_number = array_search(3, $numbers_count))
        {
            $best_hand = array();
            foreach($hand as $key => $card)
            {
                if($search_number == (int)$card)
                {
                    $best_hand[] = $card;
                    unset($hand[$key]);
                }
            }
            rsort($hand, SORT_NUMERIC);
            return array(
                'value' => $search_number,
                'best_hand' =>  array_merge($best_hand, array_slice($hand, 0, 2))
                );
        }
        return false;
    }
    
    public function isStraight($hand)
    {
        rsort($hand, SORT_NUMERIC);
        $numbers = $this->onlyNumbers($hand);
        if($numbers[0] == 14) // ace problem solved
        {
           $numbers[] = 1;
           $hand[] = str_replace(14, 1, $hand[0]);
        }
        $numbers = array_unique($numbers);
        sort($numbers);
        $i = 0;
        foreach($numbers as $number)
        {
           if($old_number+1 == $number)
            {
                $i++;
                $last_number_straight = $number;
            }
            else
               $i = 1;
            if($i == 5) break;
            $old_number = $number;
        }
        if($i > 4)
        {
           $best_hand = array();
           $placed_numbers = array();
           for($i=$last_number_straight;$i>=$last_number_straight-5;$i--)
           {
              foreach($hand as $card)
              {
                 if($i == (int)$card && !in_array((int)$card, $placed_numbers))
                 {
                    $best_hand[] = $card;
                    $placed_numbers[] = (int)$card;
                 }
              }
           }
                 
           rsort($best_hand, SORT_NUMERIC);
           return array(
               'value' => $last_number_straight,
               'best_hand' => array_slice($best_hand, 0, 5)
            );
        }
        return false;
    }
    
    public function isFlush($hand)
    {
        $suits = $this->onlySuits($hand);
        $suits_count = array_count_values($suits);
        $max = max($suits_count);
        if($max < 5)   return false;
        if($search_suits = array_search($max, $suits_count))
        {
            $best_hand = array(); 
            foreach($hand as $card)
            {
                if($search_suits == substr($card, -1))
                    $best_hand[] = $card;
            }
            rsort($best_hand, SORT_NUMERIC);
            return array(
                'value' => (int)$best_hand[0],
                'best_hand' => array_slice($best_hand, 0, 5)
            );
        }
        return false;
    }
    
    public function isFullHouse($hand)
    {
       $numbers = $this->onlyNumbers($hand);
       $numbers_count = array_count_values($numbers);
       krsort($numbers_count);
       $pair = array_search(2, $numbers_count);
       $set = array_search(3, $numbers_count);
       if($pair && $set)
       {
         $best_hand = array();
         foreach($hand as $card)
         {
            if((int)$card == $pair OR (int)$card == $set)
               $best_hand[] = $card;
         }
         return array(
             'value' => $set.".".$pair, // trick
             'best_hand' => $best_hand
         );
       }
       return false;
    }
    
    public function isPoker($hand)
    {
        $numbers = $this->onlyNumbers($hand);
        $numbers_count = array_count_values($numbers);
        krsort($numbers_count);
        if($search_number = array_search(4, $numbers_count))
        {
            $best_hand = array();
            foreach($hand as $key => $card)
            {
                if($search_number == (int)$card)
                {
                    $best_hand[] = $card;
                    unset($hand[$key]);
                }
            }
            rsort($hand, SORT_NUMERIC);
            return array(
                'value' => $search_number,
                'best_hand' =>  array_merge($best_hand, array_slice($hand, 0, 1))
                );
        }
        return false;
    }
    
    public function isStraightFlush($hand)
    {
      $suits = $this->onlySuits($hand);
      $suits_count = array_count_values($suits);
      $max = max($suits_count);
      if($max < 5)   return false;
      if($search_suits = array_search($max, $suits_count))
      {
          $best_hand = array(); 
          foreach($hand as $card)
          {
              if($search_suits == substr($card, -1))
                  $best_hand[] = $card;
          }
          return $this->isStraight($best_hand);
      }
      return false;
    }
    
    public function evaluateHands()
    {
       foreach($this->hole_cards as $player => $hole_card)
        {
           $hand = array_merge($hole_card, $this->showdown);
           $this->player_hand[$player] = $this->evaluateHand($hand);
           $this->winning_hands[$player] = $this->cryptHand($this->player_hand[$player]['hand_value']['best_hand']);
           $this->players_points[$player] = $this->player_hand[$player]['point'];
        }
        return $this;
    }
    
    public function getWinners()
    {
       $max_point = max($this->players_points);
       $tmp_winners = array();
       foreach($this->players_points as $player => $point)
       {
          if($point == $max_point)
          {
             $tmp_winners[] = $player;
             $player_hand_value[$player] = $this->player_hand[$player]['hand_value']['value'];
          }
       }
       
       $this->winners = array();
       $max_hand_value = max($player_hand_value);
       foreach($tmp_winners as $tmp_winner)
       {
          if($player_hand_value[$tmp_winner] == $max_hand_value)
             $this->winners[] = $tmp_winner;
       }
    }
}
?>