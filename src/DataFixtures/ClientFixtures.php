<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Contact;
use App\Entity\ContactType;
use App\Entity\Document;
use App\Entity\File;
use App\Entity\Project;
use App\Entity\ProjectStatus;
use App\Entity\ProjectType;
use App\Entity\Task;
use App\Entity\TaskStatus;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ClientFixtures extends Fixture implements DependentFixtureInterface
{
    private Generator $faker;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();
        $projectTypes = $manager->getRepository(ProjectType::class)->findAll();
        $projectStatuses = $manager->getRepository(ProjectStatus::class)->findAll();
        $taskStatuses = $manager->getRepository(TaskStatus::class)->findAll();
        $files = $manager->getRepository(File::class)->findAll();

        for ($clientIndex = 0; $clientIndex < 10; ++$clientIndex) {
            $client = new Client();

            $email = $this->faker->unique()->safeEmail();
            $plainPassword = bin2hex(random_bytes(24));

            $client->setUsername($email);
            $client->setPassword(
                $this->passwordHasher->hashPassword(
                    $client,
                    $plainPassword
                )
            );

            $client->setToken(bin2hex(random_bytes(32)));
            $client->setTokenCreatedAt(new DateTimeImmutable());
            $client->setName(
                sprintf(
                    '%s %s',
                    $this->faker->firstName(),
                    $this->faker->lastName()
                )
            );
            $client->setDescription($this->faker->text());
            $client->setCreatedAt(
                $this->faker->dateTimeBetween('-30 days')
            );

            $manager->persist($client);

            $phone = new Contact();
            $phone->setContactType(
                $manager
                    ->getRepository(ContactType::class)
                    ->find(ContactType::TYPE_PHONE)
            );
            $phone->setValue($this->faker->phoneNumber());
            $phone->setClient($client);

            $manager->persist($phone);

            $emailContact = new Contact();
            $emailContact->setContactType(
                $manager
                    ->getRepository(ContactType::class)
                    ->find(ContactType::TYPE_EMAIL)
            );
            $emailContact->setValue($this->faker->safeEmail());
            $emailContact->setClient($client);

            $manager->persist($emailContact);

            for ($projectIndex = 0; $projectIndex < 3; ++$projectIndex) {
                $project = new Project();
                $project->setName($this->faker->name());
                $project->setClient($client);
                $project->setType(
                    $this->faker->randomElement($projectTypes)
                );
                $project->setStatus(
                    $this->faker->randomElement($projectStatuses)
                );

                $manager->persist($project);

                for ($taskIndex = 0; $taskIndex < 3; ++$taskIndex) {
                    $task = new Task();
                    $task->setTimeEstimated(
                        $this->faker->numberBetween(15, 30)
                    );
                    $task->setTimeSpent(
                        $this->faker->numberBetween(15, 30)
                    );
                    $task->setName($this->faker->name());
                    $task->setProject($project);
                    $task->setAssignee(
                        $this->faker->randomElement($users)
                    );
                    $task->setStatus(
                        $this->faker->randomElement($taskStatuses)
                    );
                    $task->setDeadline(
                        $this->faker->dateTimeBetween(
                            '-30 days',
                            '+30 days'
                        )
                    );

                    $manager->persist($task);
                }

                for ($documentIndex = 0; $documentIndex < 3; ++$documentIndex) {
                    $document = new Document();
                    $document->setName($this->faker->colorName());
                    $document->addFile(
                        $this->faker->randomElement($files)
                    );
                    $document->setClient($client);

                    $manager->persist($document);
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            FileFixtures::class,
            UserFixtures::class,
        ];
    }
}
