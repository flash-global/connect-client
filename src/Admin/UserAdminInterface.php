<?php

namespace Fei\Service\Connect\Client\Admin;

use Fei\Service\Connect\Common\Entity\User;

/**
 * Class UserInterface
 */
interface UserAdminInterface
{
    /**
     * Persist a user entity
     *
     * @param User $user
     *
     * @return User
     */
    public function create(User $user);

    /**
     * Delete a user entity by entity, its username or email
     *
     * @param User|string $user
     */
    public function delete($user);

    /**
     * Edit a user entity with another one
     *
     * @param User $formerUser
     * @param User $newNewUser
     *
     * @return User
     */
    public function edit(User $formerUser, User $newNewUser);

    /**
     * Retrieve a user entity by its username or email
     *
     * @param string $user
     *
     * @return User
     */
    public function retrieve(string $user);
}
