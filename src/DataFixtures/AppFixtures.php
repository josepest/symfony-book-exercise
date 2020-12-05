<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class AppFixtures extends Fixture
{
    private $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function load(ObjectManager $manager)
    {
        $amsterdam = new Conference();
        $amsterdam->setCity('Amsterdam');
        $amsterdam->setYear('2019');
        $amsterdam->setIsInternational(true);
        $manager->persist($amsterdam);
        
        $paris = new Conference();
        $paris->setCity('Paris');
        $paris->setYear('2020');
        $paris->setIsInternational(false);
        $manager->persist($paris);
        
        $comment1 = new Comment();
        $comment1->setConference($amsterdam);
        $comment1->setAuthor('Fabien');
        $comment1->setEmail('fabien@example.com');
        $comment1->setText('This was a great conference.');
        $comment1->setState('published');
        $manager->persist($comment1);

        $comment2 = new Comment();
        $comment2->setConference($amsterdam);
        $comment2->setAuthor('Lucas');
        $comment2->setEmail('lucas@example.com');
        $comment2->setText('I think this one is going to be moderated.');
        $manager->persist($comment2);


        $admin = new User();
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setEmail('josepe@gmail.com');
        $admin->setName('Josep')->setSurnames('Estudis');
        $admin->setPassword($this->encoderFactory->getEncoder(User::class)->encodePassword('OMPOLO010', null));
        $manager->persist($admin);

        $manager->flush();
    }
}
