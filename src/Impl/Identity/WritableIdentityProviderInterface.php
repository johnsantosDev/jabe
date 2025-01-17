<?php

namespace Jabe\Impl\Identity;

use Jabe\Identity\{
    GroupInterface,
    TenantInterface,
    UserInterface
};
use Jabe\Impl\Interceptor\SessionInterface;

interface WritableIdentityProviderInterface extends SessionInterface
{
    // users /////////////////////////////////////////////////

    /**
     * <p>Returns a new (transient) User object. The Object is not
     * yet persistent and must be saved using the {@link #saveUser(User)}
     * method.</p>
     *
     * <p>NOTE: the implementation does not validate the uniqueness of the userId
     * parameter at this time.</p>
     *
     * @param userId
     * @return an non-persistent user object.
     */
    public function createNewUser(?string $userId): UserInterface;

    /**
     * Allows saving or updates a User object
     *
     * @param user a User object.
     * @return IdentityOperationResult the operation result object.
     * @throws IdentityProviderException in case an internal error occurs
     */
    public function saveUser(UserInterface $user): IdentityOperationResult;

    /**
     * Allows deleting a persistent User object.
     *
     * @param UserId the id of the User object to delete.
     * @return IdentityOperationResult the operation result object.
     * @throws IdentityProviderException in case an internal error occurs
     */
    public function deleteUser(?string $userId): IdentityOperationResult;

    /**
     * Allows unlocking a User object.
     * @param userId the id of the User object to delete.
     * @return IdentityOperationResult the operation result object.
     * @throws AuthorizationException if the user is not ADMIN
     */
    public function unlockUser(?string $userId): IdentityOperationResult;

    // groups /////////////////////////////////////////////////

    /**
     * <p>Returns a new (transient) Group object. The Object is not
     * yet persistent and must be saved using the {@link #saveGroup(Group)}
     * method.</p>
     *
     * <p>NOTE: the implementation does not validate the uniqueness of the groupId
     * parameter at this time.</p>
     *
     * @param groupId
     * @return an non-persistent group object.
     */
    public function createNewGroup(?string $groupId): GroupInterface;

    /**
     * Allows saving a Group object which is not yet persistent.
     *
     * @param group a group object.
     * @return IdentityOperationResult the operation result object.
     * @throws IdentityProviderException in case an internal error occurs
     */
    public function saveGroup(GroupInterface $group): IdentityOperationResult;

    /**
     * Allows deleting a persistent Group object.
     *
     * @param groupId the id of the group object to delete.
     * @return IdentityOperationResult the operation result object.
     * @throws IdentityProviderException in case an internal error occurs
     */
    public function deleteGroup(?string $groupId): IdentityOperationResult;

    /**
     * <p>
     * Returns a new (transient) Tenant object. The Object is not yet
     * persistent and must be saved using the {@link #saveTenant(Tenant)} method.
     * </p>
     *
     * <p>
     * NOTE: the implementation does not validate the uniqueness of the tenantId
     * parameter at this time.
     * </p>
     *
     * @param tenantId
     *          the id of the new tenant
     * @return an non-persistent tenant object.
     */
    public function createNewTenant(?string $tenantId): TenantInterface;

    /**
     * Allows saving a Tenant object which is not yet persistent.
     *
     * @param tenant
     *          the tenant object to save.
     * @return IdentityOperationResult the operation result object.
     * @throws IdentityProviderException
     *           in case an internal error occurs
     */
    public function saveTenant(TenantInterface $tenant): IdentityOperationResult;

    /**
     * Allows deleting a persistent Tenant object.
     *
     * @param tenantId
     *          the id of the tenant object to delete.
     * @return IdentityOperationResult the operation result object.
     * @throws IdentityProviderException
     *           in case an internal error occurs
     */
    public function deleteTenant(?string $tenantId): IdentityOperationResult;

    // Membership ///////////////////////////////////////////////

    /**
     * Creates a membership relation between a user and a group. If the user is already part of that group,
     * IdentityProviderException is thrown.
     *
     * @param userId the id of the user
     * @param groupId id of the group
     * @return IdentityOperationResult the operation result object.
     * @throws IdentityProviderException
     */
    public function createMembership(?string $userId, ?string $groupId): IdentityOperationResult;

    /**
     * Deletes a membership relation between a user and a group.
     *
     * @param userId the id of the user
     * @param groupId id of the group
     * @return IdentityOperationResult the operation result object.
     * @throws IdentityProviderException
     */
    public function deleteMembership(?string $userId, ?string $groupId): IdentityOperationResult;

    /**
     * Creates a membership relation between a tenant and a user.
     *
     * @param tenantId
     *          the id of the tenant
     * @param userId
     *          the id of the user
     * @return IdentityOperationResult the operation result object.
     */
    public function createTenantUserMembership(?string $tenantId, ?string $userId): IdentityOperationResult;

    /**
     * Creates a membership relation between a tenant and a group.
     *
     * @param tenantId
     *          the id of the tenant
     * @param groupId
     *          the id of the group
     * @return IdentityOperationResult the operation result object.
     */
    public function createTenantGroupMembership(?string $tenantId, ?string $groupId): IdentityOperationResult;

    /**
     * Deletes a membership relation between a tenant and a user.
     *
     * @param tenantId
     *          the id of the tenant
     * @param userId
     *          the id of the user
     * @return IdentityOperationResult the operation result object
     */
    public function deleteTenantUserMembership(?string $tenantId, ?string $userId): IdentityOperationResult;

    /**
     * Deletes a membership relation between a tenant and a group.
     *
     * @param tenantId
     *          the id of the tenant
     * @param groupId
     *          the id of the group
     * @return IdentityOperationResult the operation result object.
     */
    public function deleteTenantGroupMembership(?string $tenantId, ?string $groupId): IdentityOperationResult;
}
