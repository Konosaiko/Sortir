<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 *
 * @method Sortie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sortie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sortie[]    findAll()
 * @method Sortie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function findAllSorties(array $filterOptions)
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.users', 'users') // Précharge les utilisateurs inscrits
            ->addSelect('users')
            ->leftJoin('s.place', 'place') // Précharge les campus
            ->addSelect('place')
            ->leftJoin('s.etat', 'etat') // Précharge les états
            ->addSelect('etat');

        // Filtrage par campus
        if (!empty($filterOptions['campus'])) {
            $qb->andWhere('place.nom = :campus')
                ->setParameter('campus', $filterOptions['campus']);
        }

        // Filtrage par nom
        if (!empty($filterOptions['nom'])) {
            $qb->andWhere('s.nom LIKE :nom')
                ->setParameter('nom', '%' . $filterOptions['nom'] . '%');
        }

        // Filtrage par dates
        if (!empty($filterOptions['date1']) && !empty($filterOptions['date2'])) {
            $date1 = new \DateTime($filterOptions['date1']);
            $date2 = new \DateTime($filterOptions['date2']);
            $qb->andWhere('s.dateHeureDebut BETWEEN :date1 AND :date2')
                ->setParameter('date1', $date1)
                ->setParameter('date2', $date2);
        }

        // Filtrage par organisateur
        if (!empty($filterOptions['organisateur'])) {
            $qb->andWhere('s.user = :organisateur')
                ->setParameter('organisateur', $filterOptions['organisateur']);
        }

        // Filtrage par état 'Terminée'
        if (!empty($filterOptions['terminees'])) {
            $etatTerminee = $this->_em->getRepository(Etat::class)->findOneBy(['libelle' => 'Terminée']);
            $qb->andWhere('s.etat = :etatTerminee')
                ->setParameter('etatTerminee', $etatTerminee);
        }

        return $qb->getQuery()->getResult();
    }








    //    /**
    //     * @return Sortie[] Returns an array of Sortie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Sortie
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
