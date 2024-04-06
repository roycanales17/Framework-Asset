# FRAMEWORK

Install the bundle using Composer:

```
composer create-project roy404/framework product-name
```

Install the bundle by using git link: (Make sure to create your own directory)

```
git clone --depth=1 git@github.com:roycanales17/framework.git .
```

# REQUEST CLASS

Install the bundle using Composer:

```
composer require roy404/request
```

# DOCUMENTATION

The PHP framework is a custom-built solution aimed at enhancing code organization and promoting best practices in Object-Oriented Programming (OOPS) for PHP development. It offers a set of tools and features designed to streamline the development process and improve code maintainability.

## Key Components:

- `Artisan`:  Inspired by Laravel's Artisan Console, the framework includes a command-line interface (CLI) tool to automate repetitive tasks, such as database migrations, seeding, and code generation.
- `Routing`: The routing component provides a flexible and intuitive way to define routes for incoming HTTP requests, allowing developers to map URLs to specific controller actions.
- `Model`: The model component offers a convenient way to interact with the database using object-oriented principles, enabling developers to define and manipulate database records as PHP objects.
- `Controller`: Controllers handle incoming requests, process data from the model, and return responses to the client. They help in separating business logic from presentation logic.
- `Views and Blades`: Views are responsible for presenting data to the user, while Blades provide a way to reuse and extend common layout structures across multiple views, promoting code reusability and maintainability.
- `Middleware`: Middleware are filters that can be applied to incoming requests to perform tasks such as authentication, logging, or modifying request data before it reaches the controller.
- `Eloquent`: Eloquent is a powerful ORM (Object-Relational Mapping) that simplifies database operations by allowing developers to interact with the database using PHP objects and relationships.

## Purpose and Benefits:

The framework aims to improve code organization, maintainability, and scalability of PHP projects by enforcing best practices in OOPS and providing a set of tools to streamline development tasks. It encourages developers to write clean, modular, and reusable code, leading to more robust and maintainable applications.

## Future Development:

In future iterations, the framework could be enhanced with additional features such as authentication, authorization, caching, and validation, further solidifying its position as a comprehensive PHP development solution.
