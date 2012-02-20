<?php

namespace AnketaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use AnketaBundle\Entity\Season;

/**
 * @ORM\Entity()
 */
class Response {
    
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable="true")
     */
    private $comment;

    /**
     * @ORM\ManyToOne(targetEntity="Teacher")
     *
     * @var Teacher $teacher
     */
    private $teacher;

    /**
     * @ORM\ManyToOne(targetEntity="Subject")
     *
     * @var Subject $subject
     */
    private $subject;

    /**
     * @ORM\ManyToOne(targetEntity="StudyProgram")
     *
     * @var StudyProgram $studyProgram
     */
    private $studyProgram;
    
    /**
     * @ORM\ManyToOne(targetEntity="Season")
     *
     * @var Season $season
     */
    private $season;

    /**
     * @ORM\Column(type="text")
     */
    private $author_text;

    /**
     * @ORM\Column(type="text")
     */
    private $author_login;

    /**
     * @ORM\Column(type="text", nullable="true")
     */
    private $association;

    /**
     * @ORM\ManyToOne(targetEntity="Question")
     *
     * @var Question $question
     */
    private $question;

    public function getId() {
        return $this->id;
    }

    public function setComment($value) {
        $this->comment = $value;
    }

    public function getComment() {
        return $this->comment;
    }

    public function hasComment() {
        return !empty($this->comment);
    }

    /**
     * @param Teacher $value
     */
    public function setTeacher($value) {
        $this->teacher = $value;
    }

    /**
     * @return Teacher the teacher
     */
    public function getTeacher() {
        return $this->teacher;
    }

    /**
     * @param Subject $value
     */
    public function setSubject($value) {
        $this->subject = $value;
    }

    /**
     * @return Subject the subject
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * @param StudyProgram $value
     */
    public function setStudyProgram($value) {
        $this->studyProgram = $value;
    }

    /**
     * @return StudyProgram the subject
     */
    public function getStudyProgram() {
        return $this->studyProgram;
    }

    /**
     * @param string $value
     */
    public function setAuthorText($value) {
        $this->author_text = $value;
    }

    /**
     * @return string the author
     */
    public function getAuthorText() {
        return $this->author_text;
    }

    /**
     * @param string $value
     */
    public function setAuthorLogin($value) {
        $this->author_login = $value;
    }

    /**
     * @return string the author
     */
    public function getAuthorLogin() {
        return $this->author_login;
    }

    /**
     * @param string $value
     */
    public function setAssociation($value) {
        $this->association = $value;
    }

    /**
     * @return string the association
     */
    public function getAssociation() {
        return $this->association;
    }

    /**
     * @param Question $value
     */
    public function setQuestion($value) {
        $this->question = $value;
    }

    /**
     * @return Question the question
     */
    public function getQuestion() {
        return $this->question;
    }
    
    /**
     * @return Season
     */
    public function getSeason() {
        return $this->season;
    }

    /**
     * @param Season $season 
     */
    public function setSeason(Season $season) {
        $this->season = $season;
    }
    
}
