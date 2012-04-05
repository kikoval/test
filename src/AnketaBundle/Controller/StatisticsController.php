<?php

namespace AnketaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AnketaBundle\Entity\Question;
use AnketaBundle\Entity\CategoryType;
use AnketaBundle\Entity\Season;
use AnketaBundle\Entity\Subject;
use AnketaBundle\Entity\Response;
use DateTime;
use AnketaBundle\Lib\StatisticalFunctions;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class StatisticsController extends Controller {
    const GRAPH_PALETTE = 'ff1e1e|ff8f1e|f5f51d|b4ff1e|1eff1e';

    /**
     * @param string $season_slug if null, returns current active season
     * @returns Season the season
     */
    private function getSeason($season_slug = null) {
        $em = $this->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('AnketaBundle\Entity\Season');
        if ($season_slug === null) {
            $season = $repository->getActiveSeason();
        } else {
            $season = $repository->findOneBy(array('slug' => $season_slug));
        }
        if ($season == null) {
            throw new NotFoundHttpException('Chybna sezona: ' . $season_slug);
        }
        return $season;
    }

    /**
     * Returns appropriate comments from the set of answers
     */
    public function getAppropriateComments($answers) {
        $comments = array();

        foreach ($answers as $answer) {
            if ($answer->hasComment() && !$answer->getInappropriate()) {
                $comments[] = array('answer_id' => $answer->getId(),
                                    'comment' => $answer->getComment());
            }
        }

        return $comments;
    }
    
    /**
     * Returns number of inappropriate comments in set of answers.
     * @param type $answers
     * @return int
     */
    public function getNumberOfInappropriateComments($answers) {
        $amount = 0;
        foreach ($answers as $answer) if ($answer->hasComment() && $answer->getInappropriate()) $amount++;
        return $amount;
    }

    /**
     * @returns array(array('cnt'=>int,'title'=>string,'value'=>double))
     */
    public function getHistogramData($question, $answers) {
        if (!$question->hasOptions()) {
            return array();
        }

        $options = $question->getOptions();
        $histogram = array();
        $map = array();
        $i = 0;
        foreach ($options as $option) {
            $map[$option->getId()]=$i;
            $histogram[$i] =
                array('cnt' => 0,
                      'title' => $option->getOption(),
                      'value' => $option->getEvaluation()
                      );
            $i++;
        }

        foreach ($answers as $answer) {
            if ($answer->hasOption()) {
                $histogram[$map[$answer->getOption()->getId()]]['cnt'] += 1;
            }
        }

        return $histogram;
    }

    /**
     * Creates chart for given histogram
     */
    public function getChart(Question $question, $histogram) {
        $width = 300;
        $height = 150;

        list($c1, $c2, $c3, $c4, $c5) = explode('|', self::GRAPH_PALETTE);

        if (count($histogram) == 2) {
            $palette = array($c5, $c1);   // the first choice is the best
        }
        else if (count($histogram) == 5 && $histogram[2]['value'] == 0) {
            $palette = array($c1, $c4, $c5, $c4, $c1);   // the middle choice is the best
        }
        else if (count($histogram) == 5 && $histogram[0]['value'] > $histogram[4]['value']) {
            $palette = array($c5, $c4, $c3, $c2, $c1);   // the first choice is the best
        }
        else if (count($histogram) == 5) {
            $palette = array($c1, $c2, $c3, $c4, $c5);   // the last choice is the best
        }
        else if (count($histogram) == 4 && $histogram[0]['value'] > $histogram[3]['value']) {
            $palette = array($c5, $c4, $c2, $c1);   // the first choice is the best
        }
        else {
            $palette = array();
        }

        $titles = array_map(function ($data) { return $data['title']; }, $histogram);
        $counts = array_map(function ($data) { return $data['cnt']; }, $histogram);
        if (array_sum($counts) == 0) {
            return null;
        }
 
        // docs at http://code.google.com/apis/chart/image/docs/gallery/bar_charts.html
        $options = array(
            'cht' => 'bhs',   // bar chart, horizontal, stacked
            'chbh' => 'a',   // bar width = automatic
            'chds' => 'a',   // automatic data scaling
            'chxt' => 'x,y',   // visible axes
            // axis style: leave default color (676767) and font (11.5), align right (1) and
            // show only axis line (l) and not line with tick marks (lt)
            'chxs' => '1,676767,11.5,1,l',
            // marker style: data values (N), black, sum of each bar (-1), font size 16,
            // right-anchored placement with offset -3
            'chm' => 'N,000000,-1,,16,,r:-3',
            'chs' => $width . 'x' . $height,
            'chco' => implode('|', $palette),
            'chxl' => '1:|' . implode('|', array_reverse($titles)),
            'chd' => 't:' . implode(',', $counts)
        );

        $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') .
            '://chart.googleapis.com/chart?' . http_build_query($options);

        return array('url' => $url, 'width' => $width, 'height' => $height);
    }

    /**
     * Returns statistics for given data
     *
     * @returns array with following items:
     *  - cnt count
     *  - avg (optional) average value
     *  - median (optional) mean value
     *  - sigma (optional) standard deviation
     */
    public function getStatistics(array $histogram) {
        $data = array_map(function ($x) {return array($x['value'], $x['cnt']);}, $histogram);
        $cnt = StatisticalFunctions::cnt($data);
        $stats['cnt'] = $cnt;
        if ($cnt > 0) {
            $stats['avg'] = StatisticalFunctions::average($data);
            $stats['median'] = StatisticalFunctions::median($data);
        }
        if ($cnt > 1) {
            $stats['sigma'] = StatisticalFunctions::stddev($data);
        }
        return $stats;
    }
    
    /**
     * Check if the array contains at least one pair of different values
     * @param array $array
     * @return boolean true if the array contains at least two different values
     */
    private function hasMoreThanOneValue($array)
    {
        $first = true;
        $prev = null;
        foreach ($array as $value) {
            if (!$first) {
                if ($prev !== $value) {
                    return true;
                }
            }
            $prev = $value;
            $first = false;
        }
        return false;
    }
    
    /**
     * Function returns the statistics for a question in a format:
     *      result['results'] - array of number of options indexed by option id
     *      result['chart'] - chart of the results, or null if question has no options
     *      result['comments'] - array of comments
     * 
     * @param Question $question
     * @param array $answers 
     * 
     * @return array see function description for more info
     */
    public function processQuestion(Question $question, $answers) {
        $histogram = $this->getHistogramData($question, $answers);
        $stats = $this->getStatistics($histogram);
        $comments = $this->getAppropriateComments($answers);
        $hasComments = count($comments) > 0;
        $hasAnsweredOption = $stats['cnt'] > 0;
        if ($hasAnsweredOption) {
            foreach ($histogram as $key=>$value) {
                $histogram[$key]['portion'] = $value['cnt'] / $stats['cnt'];
            }
        }
        $evaluations = array();
        foreach ($question->getOptions() as $option) {
            $evaluations[] = $option->getEvaluation();
        }
        $hasDifferentOptions = $this->hasMoreThanOneValue($evaluations);
        if (!$hasDifferentOptions) {
            // Nema zmysel prezentovat zlozitejsiu statistiku pocitanu z rovnakych hodnot
            $stats = array('cnt' => $stats['cnt']);
        }
        if (!$hasAnsweredOption) {
            $histogram = array();
        }
        $data = array(
                'id' => $question->getId(),
                'title' => $question->getQuestion(),
                'description' => $question->getDescription(),
                'commentsAllowed' => $question->getHasComment(),
                'hasAnswer' => $hasAnsweredOption || $hasComments,
                'hasDifferentOptions' => $hasDifferentOptions,
                'comments' => $comments,
                'histogram' => $histogram,
                'chart' => $this->getChart($question, $histogram),
                'stats' => $stats,
                'numberOfInappropriateComments' => $this->getNumberOfInappropriateComments($answers),
                );

        return $data;
    }

    public function listSubjectsAction($season_slug, $category) {
        $em = $this->get('doctrine.orm.entity_manager');

        $season = $this->getSeason($season_slug);
        $categories = $em->getRepository('AnketaBundle:Subject')->getCategorizedSubjects($season);

        $items = array();
        foreach ($categories as $category_id => $subjects) {
            if ($category != null && $category != $category_id) continue;
            $links = array();
            foreach ($subjects as $subject) {
                $section = StatisticsSection::makeSubjectSection($this->container, $season, $subject);
                $links[$section->getTitle()] = $section->getStatisticsPath();
            }
            $items[$category_id] = $links;
        }

        $templateParams = array();
        $templateParams['class'] = 'subject-listing';
        $templateParams['activeMenuItems'] = array($season->getId(), 'subjects');
        if ($category) $templateParams['activeMenuItems'][] = $category;
        $templateParams['items'] = $items;
        return $this->render('AnketaBundle:Statistics:listing.html.twig', $templateParams);
    }
    
    public function listMySubjectsAction($season_slug) {
        $security = $this->get('security.context');
        if (!$security->isGranted('ROLE_TEACHER')) {
            throw new AccessDeniedException();
        }
        $user = $security->getToken()->getUser();
        
        $em = $this->get('doctrine.orm.entity_manager');
        $teacher = $em->getRepository('AnketaBundle:Teacher')->findOneBy(array('login' => $user->getUserName()));
        
        if ($teacher === null) {
            throw new NotFoundHttpException('Ucitel sa nenasiel');
        }

        $season = $this->getSeason($season_slug);
        $subjects = $em->getRepository('AnketaBundle:Subject')->getSubjectsForTeacher($teacher, $season);

        $items = array();
        foreach ($subjects as $subject) {
            $section = StatisticsSection::makeSubjectSection($this->container, $season, $subject);
            $items[$section->getTitle()] = $section->getStatisticsPath();
        }

        $templateParams = array();
        $templateParams['title'] = 'Moje predmety';
        $templateParams['activeMenuItems'] = array($season->getId(), 'my_subjects');
        $templateParams['items'] = array('' => $items);
        return $this->render('AnketaBundle:Statistics:listing.html.twig', $templateParams);
    }

    public function listStudyProgramsAction($season_slug) {
        $em = $this->get('doctrine.orm.entity_manager');

        $season = $this->getSeason($season_slug);
        $studyPrograms = $em->getRepository('AnketaBundle:StudyProgram')->getAllWithAnswers($season);

        $items = array();
        foreach ($studyPrograms as $studyProgram) {
            $section = StatisticsSection::makeStudyProgramSection($this->container, $season, $studyProgram);
            $items[$section->getTitle()] = $section->getStatisticsPath();
        }

        $templateParams = array();
        $templateParams['title'] = 'Študijné programy';
        $templateParams['activeMenuItems'] = array($season->getId(), 'study_programs');
        $templateParams['items'] = array('' => $items);
        return $this->render('AnketaBundle:Statistics:listing.html.twig', $templateParams);
    }

    public function resultsAction($section_slug) {
        $section = StatisticsSection::getSectionFromSlug($this->container, $section_slug);

        $maxCnt = 0;
        $results = array();

        $questions = $section->getQuestions();
        foreach ($questions as $question) {
            $answers = $section->getAnswers($question);
            $data = $this->processQuestion($question, $answers);
            $maxCnt = max($maxCnt, $data['stats']['cnt']);
            $results[] = $data;
        }

        $templateParams = array();
        $templateParams['section'] = $section;
        $templateParams['responses'] = $this->processResponses($section->getResponses());

        $limit = $section->getMinVoters();
        if ($maxCnt >= $limit ||
                $this->get('security.context')->isGranted('ROLE_FULL_RESULTS')) {
            $templateParams['results'] = $results;
            return $this->render('AnketaBundle:Statistics:results.html.twig', $templateParams);
        }
        else {
            $templateParams['limit'] = $limit;
            return $this->render('AnketaBundle:Statistics:requestResults.html.twig', $templateParams);
        }
    }

    private function buildMenu($activeItems = array()) {
        // TODO: to ze menu zavisi od $activeItems je dost hack
        // (vid HlasovanieController#buildMenu ze ako to ma vyzerat)
        $em = $this->get('doctrine.orm.entity_manager');
        $security = $this->get('security.context');

        $menu = array();
        $seasons = $em->getRepository('AnketaBundle:Season')->findAll();
        foreach ($seasons as $season) {
            // Add this season.
            $menu[$season->getId()] = $seasonItem = new MenuItem(
                $season->getDescription(),
                $this->generateUrl('statistics_list_general',
                    array('season_slug' => $season->getSlug())));
            if (isset($activeItems[0]) && $activeItems[0] == $season->getId()) {
                $seasonItem->expanded = true;

                // Add "General questions" under this season.
                $seasonItem->children['general'] = new MenuItem(
                    'Všeobecné otázky',
                    $this->generateUrl('statistics_list_general',
                        array('season_slug' => $season->getSlug())));

                // Add "Study programmes" under this season.
                $seasonItem->children['study_programs'] = $studyProgramsItem = new MenuItem(
                    'Študijné programy',
                    $this->generateUrl('statistics_list_programs',
                        array('season_slug' => $season->getSlug())));
                if (isset($activeItems[1]) && $activeItems[1] == 'study_programs') {
                    $studyProgramsItem->expanded = true;
                    $studyPrograms = $em->getRepository('AnketaBundle:StudyProgram')->getAllWithAnswers($season);
                    foreach ($studyPrograms as $studyProgram) {
                        // Add this study program under "Study programmes".
                        $studyProgramSection = StatisticsSection::makeStudyProgramSection($this->container, $season, $studyProgram);
                        $studyProgramsItem->children[$studyProgram->getCode()] = new MenuItem(
                            $studyProgram->getCode(), $studyProgramSection->getStatisticsPath());
                    }
                }

                // Add "Subjects" under this season.
                $seasonItem->children['subjects'] = $subjectsItem = new MenuItem(
                    'Predmety',
                    $this->generateUrl('statistics_list_subjects',
                        array('season_slug' => $season->getSlug())));
                if (isset($activeItems[1]) && $activeItems[1] == 'subjects') {
                    $subjectsByCategory = $em->getRepository('AnketaBundle:Subject')->getCategorizedSubjects($season);
                    foreach (array_keys($subjectsByCategory) as $category) {
                        // Add this category under "Subjects".
                        $subjectsItem->children[$category] = $categoryItem = new MenuItem(
                            $category,
                            $this->generateUrl('statistics_list_subjects',
                                array('season_slug' => $season->getSlug(), 'category' => $category)));
                        if (isset($activeItems[2]) && $activeItems[2] == $category) {
                            $subjectsItem->only_expanded = true;
                            $categoryItem->expanded = true;
                            foreach ($subjectsByCategory[$category] as $subject) {
                                // Add this subject under this category.
                                $subjectSection = StatisticsSection::makeSubjectSection($this->container, $season, $subject);
                                $categoryItem->children[$subject->getId()] = $subjectItem = new MenuItem(
                                    $subject->getName(), $subjectSection->getStatisticsPath());
                                if (isset($activeItems[3]) && $activeItems[3] == $subject->getId()) {
                                    $categoryItem->only_expanded = true;
                                    $teachers = $em->getRepository('AnketaBundle:Teacher')->getTeachersForSubject($subject, $season);
                                    foreach ($teachers as $teacher) {
                                        // Add this teacher under this subject.
                                        $teacherSection = StatisticsSection::makeSubjectTeacherSection($this->container, $season, $subject, $teacher);
                                        $subjectItem->children[$teacher->getId()] = new MenuItem(
                                            $teacher->getName(), $teacherSection->getStatisticsPath());
                                    }
                                }
                            }
                        }
                    }
                }

                // Add "My subjects" under this season.
                if ($security->isGranted('ROLE_TEACHER')) {
                    $seasonItem->children['my_subjects'] = new MenuItem(
                        'Moje predmety',
                        $this->generateUrl('statistics_list_my_subjects',
                            array('season_slug' => $season->getSlug())));
                }

                // Add "My comments" under this section.
                if ($security->isGranted('ROLE_TEACHER')) {
                    $seasonItem->children['my_comments'] = new MenuItem(
                        'Moje komentáre',
                        $this->generateUrl('response',
                            array('season_slug' => $season->getSlug())));
                }

                // Add "My reports" under this season.
                if ($security->isGranted('ROLE_STUDY_PROGRAMME_REPORT') || $security->isGranted('ROLE_DEPARTMENT_REPORT') || $security->isGranted('ROLE_ALL_REPORTS')) {
                    $seasonItem->children['my_reports'] = new MenuItem(
                        'Moje reporty',
                        $this->generateUrl('reports_my_reports',
                            array('season_slug' => $season->getSlug())));
                }
            }
        }

        return $menu;
    }

    public function menuAction($activeItems = array()) {
        $templateParams = array('menu' => $this->buildMenu($activeItems));

        $activeTail = null;
        $current = &$templateParams['menu'];
        foreach ($activeItems as $item) {
            if (!isset($current[$item])) {
                $activeTail = null;
                break;
            }
            $activeTail = $current[$item];
            $current[$item]->expanded = true;
            $current = &$current[$item]->children;
        }
        if ($activeTail) {
            $activeTail->active = true;
        }

        return $this->render('AnketaBundle:Hlasovanie:menu.html.twig',
                             $templateParams);
    }

    public function listGeneralAction($season_slug = null) {
        $em = $this->get('doctrine.orm.entity_manager');
        $season = $this->getSeason($season_slug);
        // TODO: by season
        $items = array();
        $categories = $em->getRepository('AnketaBundle\Entity\Category')
                         ->findBy(array('type' => 'general'));
        foreach ($categories AS $category) {
            // TODO: by season
            $items[$category->getDescription()] = array();
            $questions = $em->getRepository('AnketaBundle:Question')->getOrderedQuestions($category);
            foreach ($questions as $question) {
                $section = StatisticsSection::makeGeneralSection($this->container, $season, $question);
                $items[$category->getDescription()][$question->getQuestion()] = $section->getStatisticsPath();
            }
        }

        $templateParams = array();
        $templateParams['title'] = 'Všeobecné otázky';
        $templateParams['activeMenuItems'] = array($season->getId(), 'general');
        $templateParams['items'] = $items;
        return $this->render('AnketaBundle:Statistics:listing.html.twig', $templateParams);
    }

    /** Return true, if the current user can edit a response */
    private function userCanEditResponse(Response $response)
    {
        // user must be logged in, otherwise he doesn't have name
        if (!$this->get('security.context')->isGranted('ROLE_USER')) return false;
        
        $user = $this->get('security.context')->getToken()->getUser();
        return $user->getUserName() === $response->getAuthorLogin();
    }

    private function processResponses($responses)
    {
        $result = array();
        $em = $this->get('doctrine.orm.entity_manager');
        $userRepository = $em->getRepository('AnketaBundle:User');
        foreach ($responses as $response)
        {
            $item = array();
            $item['response'] = $response;
            $item['editable'] = $this->userCanEditResponse($response);
            // TODO: zjednotit nejak spravanie
            $item['author'] = $response->getAuthorText();
            if ($response->getAuthorLogin())
            {
                $user = $userRepository
                           ->findOneBy(array('userName' => $response->getAuthorLogin()));
                if (!empty($user)) $item['author'] = $user->getDisplayName();
            }
            if ($response->getAssociation()) {
                $item['author'] .= ' (' . $response->getAssociation() . ')';
            }
            $result[] = $item;
        }
        return $result;
    }

    public function reportInappropriateAction($season_slug, $answer_id) {
        $em = $this->get('doctrine.orm.entity_manager');
        $request = $this->get('request');

        $answer = $em->getRepository('AnketaBundle\Entity\Answer')->find($answer_id);
        if ($answer === null) {
            throw new NotFoundHttpException("Odpoveď s daným ID neexistuje");
        }
        if ($answer->getInappropriate()) {
            throw new NotFoundHttpException("Odpoveď s daným ID je už skrytá");
        }
        $comment = $answer->getComment();
        if (empty($comment)) {
            throw new NotFoundHttpException("Odpoveď s daným ID nemá komentár");
        }

        $section = StatisticsSection::getSectionOfAnswer($this->container, $answer);

        if ('POST' == $request->getMethod()) {
            $user = $this->get('security.context')->getToken()->getUser();
            $note = $request->get('note', '');

            $emailTpl = array(
                    'answer_id' => $answer_id,
                    'comment_page' => $section->getStatisticsPath(true),
                    'comment_body' => $comment,
                    'note' => $note,
                    'user' => $user);
            $sender = $this->container->getParameter('mail_sender');
            $to = $this->container->getParameter('mail_dest_new_teaching_association');    // ten isty e-mail ako teaching associations
            $body = $this->renderView('AnketaBundle:Statistics:reportMail.txt.twig', $emailTpl);

            $this->get('mailer'); // DO NOT DELETE THIS LINE
            // it autoloads required things before Swift_Message can be used

            $message = \Swift_Message::newInstance()
                            ->setSubject('FMFI ANKETA -- nevhodný komentár')
                            ->setFrom($sender)
                            ->setTo($to)
                            ->setBody($body);
            $this->get('mailer')->send($message);

            $session = $this->get('session');
            $session->setFlash('success',
                    'Ďakujeme. Vaše hlásenie spracujeme v priebehu niekoľkých dní.');

            return new RedirectResponse($section->getStatisticsPath());
        }
        else {
            return $this->render('AnketaBundle:Statistics:reportForm.html.twig', array(
                'section' => $section,
                'season' => $this->getSeason($season_slug),
                'answer_id' => $answer_id,
                'comment_body' => $comment,
            ));
        }


    }

}
