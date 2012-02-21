<?php
/**
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @subpackage Anketa__Entity__Repository
 * @author     Jakub Marek <jakub.marek@gmail.com>
 */

namespace AnketaBundle\Entity;

use Doctrine\ORM\EntityRepository;
use AnketaBundle\Entity\Department;

/**
 * Repository class for Program Entity
 */

class DepartmentRepository extends EntityRepository {

    public function findByUser($user, $season) {
        // TODO
        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT d
                           FROM AnketaBundle\\Entity\\UserSeasonDepartment usd,
                           AnketaBundle\\Entity\\Department d,
                           AnketaBundle\\Entity\\UserSeason us
                           WHERE us.user = :user
                           AND us.season = :season
                           AND usd.userSeason = us
                           AND usd.department = d
                           ORDER BY d.name ASC");
        $query->setParameter('user', $user);
        $query->setParameter('season', $season);

        return $query->getResult();
    }
    
    public function findByTeacherLogin($login) {
        // TODO vyhodit
        $em = $this->getEntityManager();
        $query = $em->createQuery("SELECT d
                           FROM AnketaBundle\\Entity\\Teacher t,
                           AnketaBundle\\Entity\\Department d
                           WHERE t.login = :login
                           AND t.department = d
                           ORDER BY d.name ASC");
        $query->setParameter('login', $login);

        return $query->getResult();
    }

}