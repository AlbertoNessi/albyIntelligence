<?php

namespace App\Controller;

use App\Entity\Contacts;
use App\Entity\Emails;
use App\Entity\Events;
use App\Entity\Messages;
use App\Entity\Notes;
use App\Services\GetTableDataService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ListController extends AbstractController
{
    #[Route('/list/{id}', name: 'list_url')]
    public function list(EntityManagerInterface $entityManager, int $id, GetTableDataService $getTableDataService): Response
    {
        $columns = $getTableDataService->getColumnsByTableId($id);
        $tableName = $getTableDataService->getTableNameByTableId($id);
        $data = $getTableDataService->getTableDataByTableId($entityManager, $id);

        /*dd($data);*/
        return $this->render('list/list.html.twig', [
            'list' => $id,
            'columns' => $columns,
            'tableName' => $tableName,
            'table_id' => $id,
            'data' => $data
        ]);
    }

    #[Route('/add_new_row', name: 'addNewRow_url')]
    public function addNewRow(EntityManagerInterface $entityManager, Request $request): Response
    {
        $parameters = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if ($parameters['tableName'] === "contatti") {
            $newRow = new Contacts();

            $newRow->setName($parameters['name']);
            $newRow->setSurname($parameters['surname']);
            $newRow->setPhone($parameters['phone']);
            $newRow->setEmail($parameters['email']);
        } elseif ($parameters['tableName'] === "emails") {
            $newRow = new Emails();

            $newRow->setMessage($parameters['message']);
            $newRow->setObject($parameters['object']);
            $newRow->setReceivers($parameters['receiver']);
            $newRow->setSender($parameters['sender']);
        } elseif ($parameters['tableName'] === "messaggi") {
            $newRow = new Messages();

            $newRow->setMessage($parameters['message']);
            $newRow->setSender($parameters['sender']);
            $newRow->setReceiver($parameters['receiver']);
        } elseif ($parameters['tableName'] === "note") {
            $newRow = new Notes();
            $newRow->setNote($parameters['note']);
        } elseif ($parameters['tableName'] === "eventi") {
            $newRow = new Events();

            $newRow->setNote($parameters['note']);
            $newRow->setDate(new \DateTime($parameters['date']));
            $newRow->setTitle($parameters['title']);
            $newRow->setSubtitle($parameters['subtitle']);
        }

        $entityManager->persist($newRow);
        $entityManager->flush();

        return new Response("Success!");
    }
}
