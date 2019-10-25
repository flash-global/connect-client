<?php

namespace Fei\Service\Connect\Client;

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
    public function persist(User $user);

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
     * @param User $newUser
     *
     * @return User
     */
    public function edit(User $formerUser, User $newUser);

    /**
     * Retrieve a user entity by its username or email
     *
     * @param string $user
     *
     * @return User
     */
    public function retrieve(string $user);
}
