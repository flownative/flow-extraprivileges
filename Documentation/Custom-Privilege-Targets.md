# Custom Privilege Targets

The Flow Framework offers so-called privileges to secure applications and data in the context of security concepts. These can be used to define PrivilegeTargets to restrict access to the recorded resources via roles.

## Status quo

Currently, Flow allows the protection of method invocations (`MethodPrivilege`) and entities (`EntityPrivilege`). In the area of method calls, the call of any method can be prevented completely or depending on the parameters passed. Entities can be restricted in their readability as a whole.

Securing of method invocations is possible for all functions in PHP that are accessible to the AOP subsystem, these are normally all classes in flow packages as well as large parts of the flow framework per se.

The securing of entities is used for all accesses made via the Doctrine ORM (query builder or DQL).

## Conditions

It requires the protection of entities beyond restricting the readability of complete objects:

- readability of individual properties
- Delete objects
- Change objects
- Change individual properties on objects
- Create objects

> ** Excursus: Why a `CreateEntityPrivilege`? **
>
> While changes and deletions of entities are difficult to secure using method privilege, it is relatively easy to create entities using method privilege. For this purpose, the access to the entity's con fi guration can be restricted accordingly.
>
> A new privilege makes sense for two reasons:
>
> - the semantic proximity to the other entity privileges increases the clarity.
> - the new privilege prevents the saving of new objects, not their instantiation. This can be the more meaningful variant depending on the application.

In the course of the discussion it was rejected to pursue the topic of the readability of individual properties of objects. The reason is the strong impact on the integrity of the domain model:

- If a property is restricted in its readability, what does that mean for changes (is the value set to `null`)?
- How do validation rules behave, such as non-optional properties?

Therefore, this should be done with explicit mechanisms, such as own read-models or Doctrines "partial objects".

## Possible implementation strategies

To implement your own privilege in Flow, there is the `PrivilegeInterface`, of which` MethodPrivilegeInterface` and `EntityPrivilegeInterface` are derived. An `AbstractPrivilege` can serve as a basis. For the actual enforcement of privileges, however, the corresponding restrictions must also be enforced in Flow.

This is done in the security subsystem of Flow for Method Privileges using the "Interceptor" (`InterceptorInterface`, especially` PolicyEnforcement` and `AfterInvocation`). These are called via the AOP woven PolicyEnforcementAspect. The entity privileges are implemented using the filter mechanism offered in Doctrine: Restrictions on readability are translated directly into SQL filters; this is done by the `SqlFilter` registered in the configuration.

For the required extension of the available entity privileges both mechanisms are not sufficient:

- The filters apply Doctrine only for read accesses, filtered data are "non-existent" from the user's point of view. Therefore, updates or the creation of objects can not be prevented.
- The method privileges can only help indirectly. Although it is possible to secure appropriate `ActionController`s that serve to create or modify objects, however, it is easily possible to write new code. If this is not also secured, a gap remains open.

The following options were evaluated:

- Securing the PersistenceManager via AOP. In Flow, objects are normally (though often indirectly) managed by the central PersistenceManager. Here one could extend by AOP the appropriate methods `update()` or `add()` by the necessary examinations. However, using the background Doctrine EntityManager bypasses these measures in the simplest way.
- Use of Doctrine Lifecycle events. This can be done via the `onFlush` event at the time of actual storage of data a hedge. This also covers the use of the Doctrine EM and also affects the use of DQL (in the case of `onFlush`, some other events are not active for DQL).

## Concrete implementation

The new functions are to be integrated directly into Flow, as a new feature they could be added to the next minor release, if implemented in a backward compatible way.

Based on the described possibilities, we have decided to use the event mechanism in Doctrine. The following ingredients are needed:

### Syntax for policy files

The rules for securing with the new mechanisms also require a new or extended syntax.

    privilegeTargets:
      'Neos\Flow\Security\Authorization\Privilege\Entity\CreatePrivilege':    
        'Acme.Com:CreateInvoice':
          matcher: 'isType("Acme\Com\Domain\Model\Invoice")'
      'Neos\Flow\Security\Authorization\Privilege\Entity\UpdatePrivilege':    
        'Acme.Com:UpdateInvoice':
          matcher: 'isType("Acme\Com\Domain\Model\Invoice")'
      'Neos\Flow\Security\Authorization\Privilege\Entity\DeletePrivilege':    
        'Acme.Com:DeleteInvoice':
          matcher: 'isType("Acme\Com\Domain\Model\Invoice")'

In the matcher expressions, the already familiar `property` functions of` EntityPrivilege` should be usable:

    'Neos\Flow\Security\Authorization\Privilege\Entity\UpdatePrivilege':    
      'Acme.Com:UpdateSmallInvoice':
        matcher: >-
          isType("Acme\Com\Domain\Model\Invoice")
          && property("totalAmount") < 1000

To check for changes the

- new value is used in `CreatePrivilege`
- the old value is usded in `DeletePrivilege`
- and both the new and old value are used in `UpdatePrivilege`.

The following example shows why both "old" and "new" are used in `UpdatePrivilege`:

    'Neos\Flow\Security\authorization\Privilege\Entity\UpdatePrivilege':
      'Acme.Com:UpdateBigInvoide':
        matcher: isType("Acme\Com\Domain\Model\Invoice") && property("totalAmount") > 1000

- If only the new value is used, one could change an invoice with a total of 10,000 to a new total of only 800.
- If only the old value is used, one could change an invoice with a total of 500 to a new total of only 12.000.

Both changes should not be possible. Thus when updating an entity the `property("x")` expression checks both oldf and new value. Later the option to declare which of the values to check might be added, to allow for more flexibility.

To provide the ability to protect only certain properties of an entity from changes, a new expressions feature is introduced:

    'Neos\Flow\Security\Authorization\Privilege\Entity\UpdatePrivilege':    
      'Acme.Com:UpdateInvoiceRecipient':
        matcher: >-
          isType("Acme\Com\Domain\Model\Invoice")
          && updatesProperty(["recipient", "account"])

The privilege will only be evaluated in this case if `recipient` _or_ `account` (or both) are changed. This does not affect the evaluation of other rules that may match.

### Comparison of identity

For properties not containing literal values but referencing other objects, a change is seen if the identity of a referenced object changes. A change to properties of the referenced project is thus not seen as a change to the referencing object.

For collections of values, the count of values and their identity are considered for equality. For an array of integers this means that adding, removing or replacing a value is considered a change. No change is to be detected, if simply the order of the values differs.

For a collection of objects the same principle applies: Adding, removing or replacing objects triggers a change to be seen. Changing properties of the contained objects does not trigger a change (unless those properties influence the object identity.)

### Behavior in case of error

If the system rejects an operation based on the new privilege, the behavior of the system largely matches the behavior of validation errors:

- Calling `persistAll()` will eventually lead to the `onFlush` event.
- An exception is thrown in the EventListener in the event of an error.
- The u.U. already started database transaction is discarded.

The application can not respond to this error in the normal case, the system is already in the shutdown phase, the response has already been sent.

In order to treat missing rights in the application in a user-friendly way, the active check is necessary, for example with the `ifAccess` ViewHelper or the` PrivilegeManager`.

### Privileges

The following new privileges are required:

- `Entity\CreatePrivilege`
- `Entity\UpdatePrivilege`
- `Entity\DeletePrivilege`

The naming is based on common names, such as * CRUD *, `deleteAction()` on controllers and `DELETE` in DQL. This seems intuitive, even though the RepositoryInterface has a `remove()` method.

The existing `EntityPrivilege` should ideally be replaced by an` Entity\ReadPrivilege`. This would make the naming consistent. Alternatively, the new privileges can be named differently to reduce the discrepancy to the existing `EntityPrivilege`:

- `EntityCreatePrivilege`
- `EntityUpdatePrivilege`
- `EntityDeletePrivilege`

The introduction of an EntityReadPrivilege also makes sense here.

### Event Listener

An EventListener for the onFlush event is required. It is intended to use `PrivilegeEnforcementListener.onFlush()` in the namespace `Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine`.

### Eel-Helper

For the `updatesProperty()` function, a new helper has to be implemented.

### customization `ifAccess`-ViewHelper

To check for allowed operations, the `ifAccess` ViewHelper is used. This needs to use the new privilege an additional argument to pass the affected object, I propose `subject`.
