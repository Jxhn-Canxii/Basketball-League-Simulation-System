<?php

namespace App\Http\Controllers;

use App\Models\Conference;
use Illuminate\Http\Request;
use App\Http\Controllers\HelperController;
use Illuminate\Support\Facades\DB;

class ScoutController extends Controller
{
    protected $helper;

    public function __construct()
    {
        $this->helper = new HelperController();
    }
   
    public function generateScoutingReport($scoutingReportData){
       
        $award =  $this->awardReport($scoutingReportData['awards'],$scoutingReportData['season_count']);
        $potential = $this->potential($scoutingReportData['potential_rating']);
        $overall = $this->overallReport($scoutingReportData['overall_rating']);
        $defense = $this->defenseReport($scoutingReportData['defense_rating']);
        $shooting = $this->shootingReport($scoutingReportData['shooting_rating']);
        $passing = $this->passingReport($scoutingReportData['passing_rating']);
        $freeThrow = $this->freeThrowReport($scoutingReportData['free_throw_rating']);
        $leadership = $this->leaderShip($scoutingReportData['leadership_rating']);
        $basketBallIq = $this->basketBallIq($scoutingReportData['basketball_iq_rating']);
        $workEthic = $this->workEthic($scoutingReportData['work_ethic_rating']);
        $injuryReport = $this->injuryReport($scoutingReportData['injury_prone_rate']);

        $report = "In terms of potential, ".$potential;

        $report .= "As you can see the defense, ".$defense;
        $report .= " In shooting, ".$shooting;
        $report .= " Let's go to the passing, ".$passing;
        $report .= " In terms of leadership, ".$leadership;
        $report .= " Basketball IQ, ".$basketBallIq;
        $report .= ". Work-ethic wise, ".$workEthic;
        $report .= " Let me add in free-throw, ".$freeThrow;
        $report .= " And lastly is this player injury-prone? The answer is, ".$injuryReport;
        $report .= " Overall, ".$overall;

        return $report;
    }

    private function potential($rating){
        switch ($rating) {
            case $rating > 95:
                return 'This player has a franchise-cornerstone potential.';
                break;
            case $rating > 85 && $rating < 95:
                return 'Is an all-star caliber player potential.';
                break;
            case $rating > 75 && $rating < 85:
                return 'Maybe poor to mediocre potential.';
                break;
            case $rating > 60 && $rating < 75:
                return 'As per scouts this is player has a low ceiling.';
                break;
            case $rating < 60:
                return 'As per scouts this is player has a no potential.';
                break;
            default:
                return 'No report available';
                break;
        }
    }

    private function defenseReport($rating){
        switch ($rating) {
            case $rating > 95:
                return 'One of the best defensive players of this draft!.';
                break;
            case $rating > 85 && $rating < 95:
                return 'High defensive effort!';
                break;
            case $rating > 75 && $rating < 85:
                return 'Needs to work-out on defense.';
                break;
            case $rating > 20 && $rating < 75:
                return 'He is a walking defensive liability.';
                break;
            default:
                return 'No report available';
                break;
        }
    }

    private function shootingReport($rating){
        switch ($rating) {
            case $rating > 95:
                return 'Gifted shooter!.';
                break;
            case $rating > 85 && $rating < 95:
                return 'Can contribute in offense!';
                break;
            case $rating > 75 && $rating < 85:
                return 'Not so empressive.';
                break;
            case $rating > 20 && $rating < 75:
                return 'Brick-master!.';
                break;
            default:
                return 'No report available';
                break;
        }
    }

    private function passingReport($rating){
        switch ($rating) {
            case $rating > 95:
                return 'A gifted facilitator and a floor general';
                break;
            case $rating > 85 && $rating < 95:
                return 'Can be a secondary to main facilitator!';
                break;
            case $rating > 75 && $rating < 85:
                return 'Passing is mediocre.';
                break;
            case $rating > 20 && $rating < 75:
                return 'Ball-hog!';
                break;
            default:
                return 'No report available';
                break;
        }
    }

    private function freeThrowReport($rating){
        switch ($rating) {
            case $rating > 95:
                return 'Free-throw is automatic!';
                break;
            case $rating > 85 && $rating < 95:
                return 'Serviceable free-throw shooter.';
                break;
            case $rating > 75 && $rating < 85:
                return 'Mediocre.';
                break;
            case $rating > 60 && $rating < 75:
                return "Can't rely on crunch time";
                break;
            default:
                return 'No report available';
                break;
        }
    }

    private function overallReport($rating){
        switch ($rating) {
            case $rating > 95:
                return 'Can make immediate impact in the court.';
                break;
            case $rating > 85 && $rating < 95:
                return 'Need some training to do.';
                break;
            case $rating > 75 && $rating < 85:
                return 'Double-time on training. Can catch up soon.';
                break;
            case $rating > 20 && $rating < 75:
                return 'Potentially a development player!';
                break;
            default:
                return 'No report available';
                break;
        }
    }

    private function workEthic($rating){
        switch ($rating) {
            case $rating > 95:
                return 'Mamba mentality!';
                break;
            case $rating > 85 && $rating < 95:
                return 'High work ethic! But not the best!';
                break;
            case $rating > 75 && $rating < 85:
                return 'Mediocre work ethic!';
                break;
            case $rating > 20 && $rating < 75:
                return 'Lazy player';
                break;
            default:
                return 'No report available';
                break;
        }
    }

    private function basketBallIq($rating){
        switch ($rating) {
            case $rating > 95:
                return 'Potential Point God IQ!';
                break;
            case $rating > 85 && $rating < 95:
                return 'High IQ! But not the best!';
                break;
            case $rating > 75 && $rating < 85:
                return 'Mediocre IQ!';
                break;
            case $rating > 20 && $rating < 75:
                return 'Dumb player';
                break;
            default:
                return 'No report available';
                break;
        }
    }

    private function leaderShip($rating){
        switch ($rating) {
            case $rating > 95:
                return 'Locker-room player and a future coach!';
                break;
            case $rating > 85 && $rating < 95:
                return 'High Leadership potential! But not the best!';
                break;
            case $rating > 75 && $rating < 85:
                return "Mediocre leadership.";
                break;
            case $rating > 20 && $rating < 75:
                return 'No potential to be a leader at all!';
                break;
            default:
                return 'No report available';
                break;
        }
    }

    private function injuryReport($rating){
        switch ($rating) {
            case $rating > 85 && $rating < 95:
                return 'Yes, High-chance of injury! Potential injury-prone player.';
                break;
            case $rating > 75 && $rating < 85:
                return "Maybe, Just handle it with care!";
                break;
            case $rating > 50 && $rating < 75:
                return 'Maybe, Can be healthy in a season!';
                break;
            default:
                return 'No, Lower-chance for injury!';
                break;
        }
    }

    private function awardReport($awardCount,$championshipCount){
        $report = '';
        if($awardCount > 0 && $championshipCount > 0){
           $report = 'Award-winner and a championship experience.'; 
        }
        if($awardCount == 0 && $championshipCount > 0){
           $report = 'Championship experience.'; 
        }

        return $report;
    }


}
