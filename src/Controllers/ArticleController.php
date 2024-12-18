<?php

namespace Controllers;

use Exception;
use Helpers\PrivilegeRedirect;
use Helpers\ReplaceHelper;
use Logic\Article;
use Logic\Router;
use Logic\Validator;
use Models\ArticleModel;
use Models\DatabaseConnector;

class ArticleController
{
    /**
     * @var string $page
     */
    private string $page = ROOT . 'src/Views/article.php';
    private string $editorPage = ROOT . 'src/Views/article-editor.php';

    /**
     * @var string $subPage
     */
    private string $subPage;

    private Validator $validator;

    /**
     * Constructor
     * @param string|null $subPage
     */
    public function __construct(?string $subPage = '')
    {
        $privilegeRedirect = new PrivilegeRedirect();
        $this->validator = new Validator();
        $this->subPage = $subPage ?? '';

        switch ($this->subPage) {
            case 'get':
                $this->getArticles();
                break;
            case 'add':
            case 'edit':
                $privilegeRedirect->redirectUser();
                $this->page = $this->editorPage;
                break;
            case 'delete':
                $privilegeRedirect->redirectUser();
                $this->deleteArticle();
                break;
            default:
                break;
        }
    }

    /**
     * Render webpage
     * @throws Exception
     */
    public function render(): void // TODO: Fix the slash /01... in the URL
    {
        switch ($this->subPage) {
            case null:
                Router::redirect(path: 'news', query: ['error' => 'articleNotFound']);
                break;
            case 'add':
            case 'edit':
                $article = Article::getArticleById($_GET['id'] ?? null);
                $this->page = $this->editorPage;
                break;
            case 'delete':
                break;
            default: $article = Article::getArticleBySlug($this->subPage);
        };

        $user = $_SESSION['user_data'] ?? null;
        $type = $this->subPage;
        require_once $this->page; // Load page content
    }


    /**
     * Get articles from DB
     */
    public function getArticles(): void
    {
        $search = $_GET['search'] ?? null;
        $sort = $_GET['sort'] ?? null;
        $page = $_GET['page'] ?? 1;

        $conditions = ($search) ? "WHERE id LIKE $search OR title LIKE '$search' OR subtitle LIKE '$search' OR 
                                    content LIKE '$search' or author LIKE '$search' OR created_at LIKE '$search'" : "";
        $conditions .= ($sort) ? " ORDER BY $sort" : "";
        $conditions .= ($page) ? " LIMIT 10 OFFSET " . ($page - 1) * 10 : "";

        try {
            $articlesData = ArticleModel::selectArticles(
                conditions: $conditions,
            );

            if (!$articlesData) {
                throw new Exception('Žádné články nebyly nalezeny');
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }

        echo json_encode($articlesData);
        exit();
    }

    /**
     * Add article to DB
     * @return void
     * @throws Exception
     */
    public function addArticle(): void
    {
        try {
            // Get data from $_POST
            $title = $_POST['title'] ?? null;
            $subtitle = $_POST['subtitle'] ?? null;
            $content = $_POST['content'] ?? null;
            $author = $_POST['author'] ?? null;
            $images = $_FILES['images']['error'][0] === 0 ? $_FILES['images'] : null;

            $this->validator->validateArticle(
                title: $title,
                subtitle: $subtitle,
                content: $content,
                images: $images,
            );

            $slug = ReplaceHelper::getUrlFriendlyString($title);
            $articleId = DatabaseConnector::selectMaxId('article') + 1;
            $imagePaths = [];

            if (isset($images)) {
                for ($i = 0; $i < count($images['size']); $i++) {
                    $imagePath = 'assets/uploads/articles/' . $articleId . '_' . $i .'.'. explode('/', $images['type'][$i])[1];
                    $imagePaths[] = $imagePath; // Add to array

                    move_uploaded_file( // Save to server location
                        from: $images['tmp_name'][$i],
                        to: $imagePath,
                    );
                }
            }
            
            ArticleModel::insertArticle(
                title: $title,
                subtitle: $subtitle,
                content: $content,
                slug: $slug,
                imagePaths: $imagePaths,
                authorId: $author,
            );

            Router::redirect(path: "articles/$slug", query: ['success' => 'articleAdded']);
        } catch (Exception $e) {
            Router::redirect(path: 'articles/add', query: ['error' => 'articleAddError', 'errorDetails' => $e->getMessage()]);
        }
    }

    /**
     * Edit article in DB
     * @return void
     */
    public function editArticle(): void
    {
        try {
            $id = $_POST['id'] ?? null;
            $title = $_POST['title'] ?? null;
            $subtitle = $_POST['subtitle'] ?? null;
            $content = $_POST['content'] ?? null;
            $images = $_FILES['images'] ?? null;

            $this->validator->validateArticle(
                title: $title,
                subtitle: $subtitle,
                content: $content,
                images: $images,
            );

            try {
                $imagePaths = ArticleModel::selectArticle(conditions: 'WHERE id = '. $id)['image_paths'];
                $lastImageId = 0;

                // Get last imgid
                foreach ((array)scandir('assets/uploads/articles') as $file) {
                    if (str_starts_with((string)$file, $id . '_') and (int)explode('_', (string)$file)[1] > $lastImageId) {
                        $lastImageId = (int)explode('_', (string)$file)[1];
                    }
                }

                $lastImageId++;

                for ($i = 0; $i < count($images['size']); $i++) {
                    $imagePath = 'assets/uploads/articles/' . $id . '_' . $lastImageId .'.'. explode('/', $images['type'][$i])[1];
                    $imagePaths[] = $imagePath; // Add to array

                    move_uploaded_file( // Save to server location
                        from: $images['tmp_name'][$i],
                        to: $imagePath,
                    );

                    $lastImageId++;
                }

                ArticleModel::updateArticle(
                    id: $id,
                    title: $title,
                    subtitle: $subtitle,
                    content: $content,
                    imagePaths: $images,
                );

                Router::redirect(path: 'articles', query: ['success' => 'articleEdited']);
            } catch (Exception $e) {
                Router::redirect(path: 'articles/edit', query: ['id' => $id, 'error' => 'articleEditError', 'errorDetails' => $e->getMessage()]);
            }
        } catch (Exception $e) {
            Router::redirect(path: 'articles/edit', query: ['error' => 'articleEditError', 'errorDetails' => $e->getMessage()]);
        }
    }

    public function deleteArticle(): void
    {
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'Chybí ID článku']);
        } else {
            try {
                switch (true) {
                    case isset($_GET['img']):
                        $img = substr($_GET['img'], 1); // Remove the slash infront of the image path

                        $imagePaths = explode(',', ArticleModel::selectArticle(conditions: 'WHERE id = '. $_GET['id'])['image_paths']); // Get image paths
                        $newImagePaths = array_diff($imagePaths, [$img]); // Remove the unwanted one
                        $newImagePaths = (count($newImagePaths) === 0) ? ['null'] : $newImagePaths;

                        ArticleModel::updateArticle(id: $_GET['id'], imagePaths: $newImagePaths); // Set new image paths

                        unlink($img); // Remove img from server files

                        echo json_encode(['success' => 'imageDelete']);
                        break;
                    default:
                        ArticleModel::removeArticle(id: $_GET['id']); // Delete article

                        foreach ((array)scandir('assets/uploads/articles') as $file) { // Remove all images associated with it
                            if (strpos($file, $_GET['id'] . '_') === 0) { // if the file starts with the article ID remove it
                                unlink('assets/uploads/articles/' . $file);
                            }
                        }

                        echo json_encode(['success' => 'articleDelete']);
                }
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
        }

        exit();
    }
}
