<?php
/**
 * @copyright Copyright (c) 2012 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Entity
 * @author     Martin Sucha
 */

namespace AnketaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use AnketaBundle\Entity\Subject;
use AnketaBundle\Entity\Season;

/**
 * @ORM\Entity()
 */
class SubjectSeason {
    
    /**
     * @ORM\Id 
     * @ORM\GeneratedValue 
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Subject")
     *
     * @var Subject $subject
     */
    private $subject;
    
    /**
     * @ORM\ManyToOne(targetEntity="Season")
     *
     * @var Season $season
     */
    private $season;
    
    public function getSubject() {
        return $this->subject;
    }
    
    /**
     * Pocet studentov fakulty, ktori mali zapisany tento predmet
     * danu sezonu.
     * @ORM\Column(type="integer")
     * @param int $studentCountFacutlty
     */
    private $studentCountFaculty;
    
    /**
     * Pocet studentov, ktori mali tento predmet zapisany celkovo
     * (t.j. sem sa rata aj napr. niekto z managementu, kto mal zapisany
     *  predmet na matfyze)
     * @ORM\Column(type="integer")
     * @var int $studentCountAll
     */
    private $studentCountAll;

    public function setSubject($subject) {
        $this->subject = $subject;
    }

    public function getSeason() {
        return $this->season;
    }

    public function setSeason($season) {
        $this->season = $season;
    }

    public function getId() {
        return $this->id;
    }
    
    public function getStudentCountFaculty() {
        return $this->studentCountFaculty;
    }

    public function setStudentCountFaculty($studentCountFaculty) {
        $this->studentCountFaculty = $studentCountFaculty;
    }

    public function getStudentCountAll() {
        return $this->studentCountAll;
    }

    public function setStudentCountAll($studentCountAll) {
        $this->studentCountAll = $studentCountAll;
    }
    
}
