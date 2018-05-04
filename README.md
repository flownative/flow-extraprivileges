# Custom Entity Privileges for Flow

A package with some extra privilege implementations (to be included into Flow 5.0 eventually)

## Installation

`composer require flownative/flow-extraprivileges`

## Usage

After installing the package, a few new privileges may be used in your security policy.

### Available privileges

The package provides four new privileges:

- `Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\ReadPrivilege`
- `Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\CreatePrivilege`
- `Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\UpdatePrivilege`
- `Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\DeletePrivilege`

The `ReadPrivilege` is a drop-in replacement for the `EntityPrivilege` shipped with Flow.
It exists to lessen potential for confusion, since the name `EntityPrivilege` is rather
ambiguous, but the privilege deals only with reading of entities.

The other three privileges offer new functionality and allow to secure the creation,
updating and deletion of entities. Here is an example (to be used in *Policy.yaml*):

    privilegeTargets:
    
      # the "CreatePrivilege" is checked only for freshly created entities
      'Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\CreatePrivilege':
        'Acme.PrivilegesUser:CreateInvoice':
          # matches any "Invoice" entity
          matcher: 'q(entity).is("[instanceof Acme\PrivilegesUser\Domain\Model\Invoice]")'
        'Acme.PrivilegesUser:CreateExpensiveInvoice':
          # matches ony "Invoice" entities with a total "amount" of more than 10
          matcher: >
            q(entity).is("[instanceof Acme\PrivilegesUser\Domain\Model\Invoice]")
            && q(entity).property("amount") > 10
    
      # the "UpdatePrivilege" is checked only for existing entities that are updated
      'Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\UpdatePrivilege':
        'Acme.PrivilegesUser:UpdateInvoice':
          # matches any "Invoice" entity being updated
          matcher: 'q(entity).is("[instanceof Acme\PrivilegesUser\Domain\Model\Invoice]")'
        'Acme.PrivilegesUser:UpdateExpensiveInvoice':
          # matches only "Invoice" entities being updated with a total "amount" of more than 10
          # in either the (unchanged) "originalEntityData" or the already changed "entity"
          matcher: >
            q(entity).is("[instanceof Acme\PrivilegesUser\Domain\Model\Invoice]")
            && (q(entity).property("amount") > 10
            || q(originalEntityData).property("amount") > 10)
    
      'Flownative\Flow\ExtraPrivileges\Security\Authorization\Privilege\Entity\DeletePrivilege':
        'Acme.PrivilegesUser:DeleteInvoice':
          # matches any "Invoice" entity
          matcher: 'q(entity).is("[instanceof Acme\PrivilegesUser\Domain\Model\Invoice]")'
        'Acme.PrivilegesUser:DeleteExpensiveInvoice':
          # matches only "Invoice" entities being updated with a total "amount" of more than 10
          # in the (unchanged) "originalEntityData"
          matcher: >
            q(entity).is("[instanceof Acme\PrivilegesUser\Domain\Model\Invoice]")
            && q(originalEntityData).property("amount") > 10

##### Matcher syntax

The matcher syntax shown above differs from the syntax known from the `EntityPrivilege` in Flow
(and that is unchanged for the `Entity\ReadPrivilege` in this package). The matcher syntax is
regular Eel with support for FlowQuery, and there are two special items available in the context:

- `entity` is the actual entity that is being checked
- `originalEntityData` is an array with property values as they were loaded from the persistence

Keep in mind that checking for the type of entity is only possible on `entity`, the other
item is an array and will never match a check against a class!

##### Eel helpers

In addition to these two, Eel helpers are available in the context, as configured in the settings
with `Flownative.Flow.ExtraPrivileges.defaultContext`:

- `String`: `Neos\Eel\Helper\StringHelper`
- `Array`: `Neos\Eel\Helper\ArrayHelper`
- `Date`: `Neos\Eel\Helper\DateHelper`
- `Configuration`: `Neos\Eel\Helper\ConfigurationHelper`
- `Math`: `Neos\Eel\Helper\MathHelper`
- `Json`: `Neos\Eel\Helper\JsonHelper`
- `Security`: `Neos\Eel\Helper\SecurityHelper`
- `Type`: `Neos\Eel\Helper\TypeHelper`

### Fluid (view) integration

The `ifAccess` view helper is used to check for access to a privilege target. With the new
privileges, it has been expanded to accept the entity to check against in the parameter
`subject`.

    <f:security.ifAccess privilegeTarget="somePrivilegeTargetIdentifier" subject="{someEntity}">
       This is being shown in case you have access to the given privilege target
    </f:security.ifAccess>

## Background

Further information and details on the reasoning behind this package may be found in
[Custom Privilege Targets](Documentation/Custom-Privilege-Targets.md).

## Credits

Development of this package has been sponsored by clicsoft gmbh, Zug, Switzerland.
