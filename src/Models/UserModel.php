<?php

namespace Models;

use Logic\DatabaseException;

class UserModel
{
    /**
     * Get user data from database
     * @param int|null $id
     * @param string|null $username
     * @return array<string, float|int|string|null>|null
     * @throws DatabaseException
     */
    public static function selectUser(?int $id = null, ?string $username = null): ?array
    {
        if (!$id and !$username) {
            return null;
        }

        $condition = ($id) ? "WHERE id = $id" : "WHERE username = '$username'";

        return DatabaseConnector::select(
            table: 'user',
            items: ['*'],
            conditions: $condition,
        )[0];
    }

    /**
     * Get all users from database
     * @param string|null $conditions
     * @return array<array<string, float|int|string|null>|int<0, max>>|null
     * @throws DatabaseException
     */
    public static function selectUsers(?string $conditions = null): ?array
    {
        return DatabaseConnector::select(
            table: 'user',
            items: ['*'],
            conditions: $conditions,
        );
    }

    /**
     * Check if user exists in database
     * @param string $username
     * @return array<array<string, float|int|string|null>|int<0, max>>|null
     * @throws DatabaseException
     */
    public static function existsUser(string $username): ?array
    {
        return DatabaseConnector::select(
            table: 'user',
            items: ['username'],
            conditions: 'WHERE username LIKE "' . $username . '"',
        );
    }

    /**
     * @param string $username
     * @param string $password
     * @param string|null $profile_image_path
     * @return void
     * @throws DatabaseException
     */
    public static function insertUser(string $username, string $fullname, string $password, ?string $profile_image_path): void
    {
        $items = ['username', 'fullname', 'password', 'role'];
        $values = [$username, $fullname, $password, 'user'];

        if ($profile_image_path) {
            array_push($items, 'profile_image_path');
            array_push($values, $profile_image_path);
        }

        DatabaseConnector::insert(
            table: 'user',
            items: $items,
            values: $values,
        );
    }

    /**
     * @param int $id
     * @param string $fullname
     * @return void
     * @throws DatabaseException
     */
    public static function updateUserFullname(int $id, string $fullname): void
    {
        DatabaseConnector::update(
            table: 'user',
            items: ['fullname'],
            values: [$fullname],
            conditions: 'WHERE id = ' . $id,
        );
    }

    /**
     * @param int $id
     * @param string $profile_image_path
     * @return void
     * @throws DatabaseException
     */
    public static function updateUserProfileImage(int $id, string $profile_image_path): void
    {
        DatabaseConnector::update(
            table: 'user',
            items: ['profile_image_path'],
            values: [$profile_image_path],
            conditions: 'WHERE id = ' . $id,
        );
    }
}
