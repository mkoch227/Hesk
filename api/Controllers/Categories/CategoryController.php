<?php

namespace Controllers\Categories;

use BusinessLogic\Categories\Category;
use BusinessLogic\Categories\CategoryForTree;
use BusinessLogic\Categories\CategoryForTreeData;
use BusinessLogic\Categories\CategoryHandler;
use BusinessLogic\Categories\CategoryRetriever;
use BusinessLogic\Exceptions\ApiFriendlyException;
use BusinessLogic\Helpers;
use Controllers\JsonRetriever;

class CategoryController extends \BaseClass {
    function get($id) {
        $categories = self::getAllCategories();

        foreach ($categories as $category) {
            if ($category->id === $id) {
                return output($category);
            }
        }

        throw new ApiFriendlyException("Category {$id} not found!", "Category Not Found", 404);
    }

    static function printAllCategories() {
        output(self::getAllCategories());
    }

    private static function getAllCategories() {
        global $hesk_settings, $applicationContext, $userContext;

        /* @var $categoryRetriever CategoryRetriever */
        $categoryRetriever = $applicationContext->get(CategoryRetriever::clazz());

        return $categoryRetriever->getAllCategories($hesk_settings, $userContext);
    }

    static function getForTree() {
        global $hesk_settings, $applicationContext, $userContext;

        /* @var $categoryRetriever CategoryRetriever */
        $categoryRetriever = $applicationContext->get(CategoryRetriever::clazz());

        /* @var $categories Category[] */
        $categories = $categoryRetriever->getAllCategories($hesk_settings, $userContext);

        $transformed = array();

        $totalNumberOfTickets = 0;
        foreach ($categories as $category) {
            $totalNumberOfTickets += $category->numberOfTickets;
        }

        foreach ($categories as $category) {
            $cat = new CategoryForTree();
            $data = new CategoryForTreeData();
            $cat->id = $category->id;
            $cat->text = $category->name;
            $cat->parent = $category->parentId === null ? '#' : $category->parentId;
            $data->numberOfTickets = $category->numberOfTickets;
            $data->totalNumberOfTickets = $totalNumberOfTickets;
            $data->description = $category->description;
            $data->manager = $category->manager;
            $data->priority = $category->priority;
            $data->displayBorder = $category->displayBorder;
            $data->autoAssign = $category->autoAssign;
            $data->foregroundColor = $category->foregroundColor;
            $data->backgroundColor = $category->backgroundColor;
            $data->type = $category->type;
            $data->usage = $category->usage;
            $cat->data = $data;

            $transformed[] = $cat;
        }

        return output($transformed);
    }

    function post() {
        global $hesk_settings, $userContext, $applicationContext;

        $data = JsonRetriever::getJsonData();

        $category = $this->buildCategoryFromJson($data);

        /* @var $categoryHandler CategoryHandler */
        $categoryHandler = $applicationContext->get(CategoryHandler::clazz());

        $category = $categoryHandler->createCategory($category, $userContext, $hesk_settings);

        return output($category, 201);
    }

    /**
     * @param $json
     * @return Category
     */
    private function buildCategoryFromJson($json) {
        $category = new Category();

        $category->autoAssign = Helpers::safeArrayGet($json, 'autoassign');
        $category->backgroundColor = Helpers::safeArrayGet($json, 'backgroundColor');
        $category->catOrder = Helpers::safeArrayGet($json, 'catOrder');
        $category->description = Helpers::safeArrayGet($json, 'description');
        $category->displayBorder = Helpers::safeArrayGet($json, 'displayBorder');
        $category->foregroundColor = Helpers::safeArrayGet($json, 'foregroundColor');
        $category->manager = Helpers::safeArrayGet($json, 'manager');
        $category->name = Helpers::safeArrayGet($json, 'name');
        $category->priority = Helpers::safeArrayGet($json, 'priority');
        $category->type = Helpers::safeArrayGet($json, 'type');
        $category->usage = Helpers::safeArrayGet($json, 'usage');

        return $category;
    }

    function put($id) {
        global $hesk_settings, $userContext, $applicationContext;

        $data = JsonRetriever::getJsonData();

        $category = $this->buildCategoryFromJson($data);
        $category->id = intval($id);

        /* @var $categoryHandler CategoryHandler */
        $categoryHandler = $applicationContext->get(CategoryHandler::clazz());

        $category = $categoryHandler->editCategory($category, $userContext, $hesk_settings);

        return output($category);
    }

    function delete($id) {
        global $hesk_settings, $userContext, $applicationContext;

        /* @var $categoryHandler CategoryHandler */
        $categoryHandler = $applicationContext->get(CategoryHandler::clazz());

        $categoryHandler->deleteCategory($id, $userContext, $hesk_settings);

        return http_response_code(204);
    }

    static function sort($id, $direction) {
        global $applicationContext, $hesk_settings;

        /* @var $handler CategoryHandler */
        $handler = $applicationContext->get(CategoryHandler::clazz());

        $handler->sortCategory(intval($id), $direction, $hesk_settings);
    }
}