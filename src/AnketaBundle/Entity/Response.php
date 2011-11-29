<?php

namespace AnketaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

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
     * @ORM\Column(type="text")
     */
    private $author_text;

    /**
     * @ORM\Column(type="text")
     */
    private $author_login;

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
}
