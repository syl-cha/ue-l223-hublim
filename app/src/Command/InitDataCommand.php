<?php

namespace App\Command;

use App\Entity\Status;
use App\Enum\StatusLabel;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\StudyField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:init-data',
    description: 'Initialise les données de base (Statuts, Catégories, Filières) pour la production.',
)]
class InitDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params, // Pour récupérer le chemin vers vos JSON
        private UserPasswordHasherInterface $hasher // Pour hasher les mots de passe
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectDir = $this->params->get('kernel.project_dir');

        $io->title('Début de l\'importation des données initiales...');
        // ---------------------------------------------------------
        // 1. STATUTS
        // ---------------------------------------------------------
        $io->section('📥 Importation des statuts...');
        // Status Etudiant
        $statusStudent = new Status();
        $statusStudent->setLabel(StatusLabel::STUDENT);
        $this->entityManager->persist($statusStudent);

        // Status Enseignant
        $statusTeacher = new Status();
        $statusTeacher->setLabel(StatusLabel::TEACHER);
        $this->entityManager->persist($statusTeacher);

        // Status Personnel
        $statusStaff = new Status();
        $statusStaff->setLabel(StatusLabel::STAFF);
        $this->entityManager->persist($statusStaff);

        // ---------------------------------------------------------
        // 2. CATÉGORIES
        // Utilisez $projectDir . '/src/DataFixtures/categories.json' pour trouver votre fichier
        // ---------------------------------------------------------
        $io->section('📥 Importation des catégories...');
        $jsonCategories = file_get_contents($projectDir . '/src/DataFixtures/data/categories.json');
        $categoryData = json_decode($jsonCategories, true);

        foreach ($categoryData as $c) {
            $category = new Category();
            $category->setName($c['nom']);
            $category->setSlug($c['slug']);
            $this->entityManager->persist($category);

            if (isset($c['sous_categories'])) {
                foreach ($c['sous_categories'] as $s_c) {
                    $sousCategory = new Category();
                    $sousCategory->setName($s_c['nom']);
                    $sousCategory->setSlug($s_c['slug']);
                    $sousCategory->setParent($category);

                    $this->entityManager->persist($sousCategory);
                }
            }
        }

        // ---------------------------------------------------------
        // 2bis. DEPARTEMENTS
        // ---------------------------------------------------------
        $io->section('📥 Importation des départements...');
        $jsonDepartments = file_get_contents($projectDir . '/src/DataFixtures/data/departements.json');
        $departmentData = json_decode($jsonDepartments, true);
        $departementsAssoc = [];

        foreach ($departmentData as $d) {
            $dept = new \App\Entity\Department();
            $dept->setCode($d['code']);
            $dept->setLabel($d['label']);
            $dept->setColor($d['color']);
            $this->entityManager->persist($dept);
            $departementsAssoc[$d['code']] = $dept;
        }

        // ---------------------------------------------------------
        // 3. FILIÈRES / STUDY FIELDS
        // ---------------------------------------------------------
        $io->section('📥 Importation des filières...');
        $jsonStudyField = file_get_contents($projectDir . '/src/DataFixtures/data/filieres.json');
        $studyFieldData = json_decode($jsonStudyField, true);
        $toutesLesFilieres = []; // sauvegarde pour attribution à l'utilisateur lambda plus tard
        foreach ($studyFieldData as $f) {
            $studyField = new StudyField();
            $studyField->setName($f['nom']);
            $studyField->setType($f['type']);

            if (isset($departementsAssoc[$f['department']])) {
                $studyField->setDepartment($departementsAssoc[$f['department']]);
            }

            $this->entityManager->persist($studyField);
            $toutesLesFilieres[] = $studyField;
        }

        // ---------------------------------------------------------
        // 4. UTILISATEURS PAR DÉFAUT
        // ---------------------------------------------------------

        // création d'un admin
        $io->section('📥 Importation des utilisateurs par défaut...');
        $io->text('1/ Création d\'un compte administrateur Admin Système');
        // Email et mot de passe sont saisi lors de l'exécution de la commande par sécurité
        $adminEmail = $io->ask('Quelle adresse email pour le compte administrateur ?', 'admin@hublim.bradype.fr');
        $adminPassword = $io->askHidden('Veuillez taper le mot de passe sécurisé pour cet administrateur :');
        if (empty($adminPassword)) {
            $io->error('💣 Le mot de passe ne peut pas être vide. Relancez la commande.');
            return Command::FAILURE;
        }
        $admin = new User();
        $admin->setEmail($adminEmail);
        $admin->setFirstName('Admin');
        $admin->setLastName('Système');
        $admin->setPassword($this->hasher->hashPassword($admin, $adminPassword));
        $admin->setStudyField(null);
        $admin->setStatus($statusStaff);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setCreatedAt(new \DateTimeImmutable());
        $admin->setIsVerified(true);
        $admin->setTwoFactorSecret('none');

        $this->entityManager->persist($admin);
        $io->text('👍 L\'utilisateur John Lambda créé avec succès.');

        // création d'u utilisateur lambda
        $io->text('2/ Création d\'un compte utilisateur classique (Étudiant)');
        $userEmail = $io->ask('Quelle adresse email pour l\'utilisateur lambda ?', 'test@hublim.bradype.fr');
        $userPassword = $io->askHidden('Veuillez taper le mot de passe pour cet utilisateur :');
        if (empty($userPassword)) {
            $io->warning('Mot de passe vide : création de l\'utilisateur lambda ignorée.');
        } else {
            $user = new User();
            $user->setEmail($userEmail);
            $user->setFirstName('John');
            $user->setLastName('Lambda');
            // On hache le mot de passe saisi
            $user->setPassword($this->hasher->hashPassword($user, $userPassword));
            $filiereAuHasard = $toutesLesFilieres[array_rand($toutesLesFilieres)];
            $user->setStudyField($filiereAuHasard);
            $user->setStatus($statusStudent);
            $user->setRoles(['ROLE_USER']);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setIsVerified(true);
            $user->setTwoFactorSecret('none');

            $this->entityManager->persist($user);
            $io->text('👍 L\'utilisateur John Lambda créé avec succès.');
        }


        // On sauvegarde tout en base de données à la fin !
        $this->entityManager->flush();

        $io->success('🎉 Toutes les données initiales ont été importées avec succès !');

        return Command::SUCCESS;
    }
}
