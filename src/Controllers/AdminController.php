<?php

namespace Controllers;

use Helpers\PrivilegeRedirect;
use Logic\Article;
use Logic\DatabaseException;
use Logic\Router;
use Logic\User;
use Models\DatabaseConnector;

class AdminController extends Controller
{
    private string $path = ROOT . 'src/Views/admin.php';

    /**
     * Construct
     * Check if user is logged in when creating an instance of the class
     */
    public function __construct()
    {
        $privilegeRedirect = new PrivilegeRedirect();
        $privilegeRedirect->redirectEditor();
    }

    /**
     * Render page content
     * @throws DatabaseException
     */
    public function render(): void
    {
        $pageUsers = (isset($_GET['page-users'])) ? (int)$_GET['page-users'] : 1;
        $pageArticles = (isset($_GET['page-articles'])) ? (int)$_GET['page-articles'] : 1;

        $users = $this->loadUsers($pageUsers);
        $articles = $this->loadArticles($pageArticles);

        require_once $this->path; // Load page content
    }

    /**
     * @param int $page
     * @return array<User>
     * @throws DatabaseException
     */
    private function loadUsers(int $page = 1): array
    {
        $databaseUsers = DatabaseConnector::selectUsers();
        $users = [];

        foreach ($databaseUsers as $user) {
            $users[] = new User(
                id: (int)$user['id'],
                username: $user['username'],
                fullname: $user['fullname'],
                image: file_exists($user['profile_image_path']) ? $user['profile_image_path'] : DEFAULT_PFP,
                role: $user['role'],
                createdAt: $user['created_at'],
            );
        }

        return $users;
    }

    /**
     * @param int $page
     * @return array<Article>
     * @throws DatabaseException
     */
    private function loadArticles(int $page = 1): array
    {
        $databaseArticles = DatabaseConnector::selectArticles();
        $articles = [];

        foreach ($databaseArticles as $article) {
            $articles[] = new Article(
                id: (int)$article['id'],
                title: $article['title'],
                subtitle: $article['subtitle'],
                content: $article['content'],
                slug: $article['slug'],
                imagePaths: explode(',', $article['image_paths']),
                authorId: (int)$article['author_id'],
                createdAt: $article['created_at'],
            );
        }

        return $articles;
    }
}
