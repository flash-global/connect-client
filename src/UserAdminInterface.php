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
     * @param User $user
     */
    public function persist(User $user);

    /**
     * Delete a user entity
     *
     * @param $user
     */
    public function delete(User $user);

    /**
     * Edit a user entity with another one
     *
     * @param User $formerUser
     * @param User $newUser
     *
     * @return User
     */
    public function edit(User $formerUser, User $newUser);

}
