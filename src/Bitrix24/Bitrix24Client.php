<?php

namespace Src\Bitrix24;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;

class Bitrix24Client
{
    const ANASTASIA_CHERKASHINA = 'Anastasia Cherkashina';
    const ANASTASIA_CHERKASHINA_ID = '62';
    const YAROSLAV_STREKOPYTOV = 'Yaroslav Strekopytov';
    const YAROSLAV_STREKOPYTOV_ID = '36';
    const SLAVA_VISHNYAKOV = 'Slava Vishnyakov';
    const SLAVA_VISHNYAKOV_ID = '20';
    const DMITRIY_LEONTIEV = 'Dmitriy Leontiev';
    const DMITRIY_LEONTIEV_ID = '14';
    const ALEKSANDR_SEMENOV = 'Aleksandr Semenov';
    const ALEKSANDR_SEMENOV_ID = '108';
    const DENYS_VOROBYOV = 'Denys Vorobyov';
    const DENYS_VOROBYOV_ID = '110';
    const RENATA_PAVKSTELO = 'Renata Pavkstelo';
    const RENATA_PAVKSTELO_ID = '22';
    const VLADIMIR_SHIDLOVSKIY = 'Vladimir Shidlovskiy';
    const VLADIMIR_SHIDLOVSKIY_ID = '92';

    const RESPONSIBLE_FOR_SIGNING_USER = 'Anastasia Cherkashina';
    const PMO_INTERNAL_PROCESSES_GROUP_ID = 28;
    const ROBOT_ID = 116;

    const ITSWAP_SUPPORT_GROUP = 40;
    const ITSWAP_DEVELOPER_GROUP = 48;

    public static $testsCreateTaskTitle;
    public static $testsCreateTaskDescription;

    public function __construct()
    {
        $this->endpoint = env('BITRIX24_ENDPOINT');
    }

    public function canActuallyCreate()
    {
        if (app()->runningUnitTests()) {
            return true;
        }
        return !empty($this->endpoint);
    }

    /**
     * @param $title
     * @param $descriptionHtml
     * @param $responsibleName
     * @param array $checklist
     * @param Carbon|null $deadline
     * @param array $accompliceIds
     * @return int|null
     */
    public static function createSimpleTask(
        $title,
        $descriptionHtml,
        $responsibleName,
        $checklist = [],
        Carbon $deadline = null,
        $accompliceIds = [],
        $groupId = null
    ) {
        if (app()->runningUnitTests()) {
            self::$testsCreateTaskTitle = $title;
            self::$testsCreateTaskDescription = $descriptionHtml;

            return null;
        }

        $b = new Bitrix24Client();
        $t = new Task($title, nl2br($descriptionHtml), self::idOfUser($responsibleName));
        $t->setChecklist($checklist);
        $t->setDeadline($deadline);
        $t->setAccompliceIds($accompliceIds);
        $t->setGroupId($groupId);
        return $b->createTask($t);
    }

    public function createTask(Task $task): int
    {
        $taskId = $this->call('task.item.add.json', [
            'arNewTaskData' => [
                'TITLE' => $task->getTitle(),
                'DESCRIPTION' => $task->getDescription(),
                'RESPONSIBLE_ID' => $task->getResponsibleId(),
                'CREATED_BY' => Bitrix24Client::ROBOT_ID,
                'DEADLINE' => $task->getDeadline() ? $task->getDeadline()->format('Y-m-d\TH:i:sP') : null,
                'ACCOMPLICES' => $task->getAccompliceIds(),
                'GROUP_ID' => $task->getGroupId(),
            ]
        ]);

        foreach ($task->getChecklist() as $item) {
            $this->call('task.checklistitem.add', [$taskId, ['TITLE' => $item]]);
        }

        return $taskId;
    }

    public function listUsers()
    {
        return $this->call('user.get.json', [
        ]);
    }

    private function methodEndpoint($name)
    {
        return $this->endpoint . $name;
    }

    /**
     * @param $method
     * @param $data
     * @return mixed
     */
    private function call($method, $data)
    {
        $c = new Client(['exceptions' => false]);

        $response = $c->post($this->methodEndpoint($method), [
            'json' => $data,
        ]);

        $uuid = Uuid::uuid4()->toString();
        \Log::info("Bitrix24Client: Sent a request #$uuid to Bitrix", ['method' => $method, 'request' => $data]);

        $body = (string)$response->getBody();
        \Log::info("Bitrix24Client: Request #$uuid got response", ['body' => $body]);

        $result = \GuzzleHttp\json_decode($body);

        if (object_get($result, 'error')) {
            \Sentry::captureMessage("Bitrxi24 ERROR", [], ['request' => $data, 'response' => $body]);
            throw new \RuntimeException(html_entity_decode(strip_tags($result->error_description)));
        }

        return $result->result;
    }

    public static function idOfUser($name)
    {
        // $map = $this->userMapToId();
        // I'm a bit afraid to leave "user" permission on, because someone bad can delete all users, so it's
        // better to hardcode it for now
        $map = [
            'Yaroslav Pavlov' => '1',
            'Дмитрий Ленотьев' => '14',
            'Elina Nakvasina' => '16',
            'Evgenia Pavlova' => '18',
            self::SLAVA_VISHNYAKOV => self::SLAVA_VISHNYAKOV_ID,
            self::RENATA_PAVKSTELO => self::RENATA_PAVKSTELO_ID,
            'Maria Toumanov' => '24',
            'Eimante  Jankauskaite' => '26',
            'Pedro Iglesias' => '30',
            'Kristina Krotova' => '32',
            'Justina Druckute' => '34',
            self::YAROSLAV_STREKOPYTOV => self::YAROSLAV_STREKOPYTOV_ID,
            'Lijana Lepeskaite' => '38',
            'Ruta Vaiciulyte' => '40',
            'Alina Mustakimova' => '42',
            'Edgar Kitkovskis' => '44',
            'Vaida Maracinskaite' => '46',
            'Marina  Markina' => '48',
            'Yuliya  Gorbunova' => '50',
            'Yuliya Galushkina' => '52',
            'Sergey Shvecov' => '54',
            'Anastasiya Lobova' => '56',
            'Olga Usyukina' => '58',
            'Tatyana Ponomareva' => '60',
            self::ANASTASIA_CHERKASHINA => self::ANASTASIA_CHERKASHINA_ID,
            'Vitalina Lysova' => '64',
            'Lina Richards' => '66',
            'Maria Rasskazova' => '68',
            'Sofya Likhanova' => '70',
            'Liva Kuharenko' => '72',
            'Aya Zalova' => '74',
            'Margarita Trubizin' => '76',
        ];

        return array_get($map, $name);
    }

    private function userMapToId()
    {
        return collect($this->listUsers())->mapWithKeys(function ($user) {
            return [$user->NAME . ' ' . $user->LAST_NAME => $user->ID];
        });
    }
}