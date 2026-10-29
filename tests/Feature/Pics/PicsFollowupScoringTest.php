<?php

namespace Tests\Feature\Pics;

use App\Models\PicsFollowup;
use Tests\TestCase;

class PicsFollowupScoringTest extends TestCase
{
    public function test_amt_score_is_the_number_of_errors(): void
    {
        $data = PicsFollowup::computeScores([
            'amt_respuestas' => [true, true, true, true, true, true, true, false, false, false],
        ]);

        $this->assertSame(3, $data['amt_score']);
    }

    public function test_moca_is_only_scored_when_amt_has_three_or_more_errors_and_all_domains_filled(): void
    {
        $lowError = PicsFollowup::computeScores([
            'amt_respuestas' => array_fill(0, 9, true) + [9 => false], // 1 error
            'moca_respuestas' => array_fill_keys(array_keys(PicsFollowup::MOCA_DOMAINS), 3),
        ]);
        $this->assertNull($lowError['moca_total']);

        $highError = PicsFollowup::computeScores([
            'amt_respuestas' => [false, false, false, true, true, true, true, true, true, true], // 3 errors
            'moca_respuestas' => ['visuoespacial' => 3, 'nomenclatura' => 2, 'atencion' => 3, 'lenguaje' => 1, 'abstraccion' => 1, 'recuerdo' => 1, 'orientacion' => 4],
        ]);
        $this->assertSame(15, $highError['moca_total']);

        $incomplete = PicsFollowup::computeScores([
            'amt_respuestas' => [false, false, false, true, true, true, true, true, true, true],
            'moca_respuestas' => ['visuoespacial' => 3],
        ]);
        $this->assertNull($incomplete['moca_total']);
    }

    public function test_hads_phq9_pcptsd_ptg_picsf_sum_their_items(): void
    {
        $data = PicsFollowup::computeScores([
            'hads_respuestas' => [1, 1, 1, 1, 1, 1, 1], // 7
            'phq9_respuestas' => [1, 1, 1, 1, 1, 1, 1, 1, 1], // 9
            'pcptsd_respuestas' => [true, true, false, false, false], // 2
            'ptg_respuestas' => array_fill(0, 10, 3), // 30
            'picsf_respuestas' => [1, 1, 1, 1, 1], // 5
        ]);

        $this->assertSame(7, $data['hads_ansiedad']);
        $this->assertSame(9, $data['phq9_score']);
        $this->assertSame(2, $data['pcptsd_score']);
        $this->assertSame(30, $data['ptg_score']);
        $this->assertSame(5, $data['picsf_distress']);
    }

    public function test_incomplete_instruments_are_not_scored(): void
    {
        $data = PicsFollowup::computeScores([
            'hads_respuestas' => [1, 1, 1],
            'phq9_respuestas' => [1, 1],
        ]);

        $this->assertArrayNotHasKey('hads_ansiedad', $data);
        $this->assertArrayNotHasKey('phq9_score', $data);
    }

    public function test_positive_flags_use_the_documented_cutoffs(): void
    {
        $followup = new PicsFollowup(['amt_score' => 3, 'hads_ansiedad' => 8, 'phq9_score' => 10, 'pcptsd_score' => 3, 'picsf_distress' => 12]);
        $this->assertTrue($followup->isCognitionPositive());
        $this->assertTrue($followup->isAnxietyPositive());
        $this->assertTrue($followup->isDepressionPositive());
        $this->assertTrue($followup->isPtsdPositive());
        $this->assertTrue($followup->isFamilyDistressPositive());

        $negative = new PicsFollowup(['amt_score' => 2, 'hads_ansiedad' => 7, 'phq9_score' => 9, 'pcptsd_score' => 2, 'picsf_distress' => 11]);
        $this->assertFalse($negative->isCognitionPositive());
        $this->assertFalse($negative->isAnxietyPositive());
        $this->assertFalse($negative->isDepressionPositive());
        $this->assertFalse($negative->isPtsdPositive());
        $this->assertFalse($negative->isFamilyDistressPositive());

        $unassessed = new PicsFollowup;
        $this->assertNull($unassessed->isCognitionPositive());
    }
}
