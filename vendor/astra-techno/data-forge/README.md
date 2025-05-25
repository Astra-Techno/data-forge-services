# DataForge

[![Latest Stable Version](https://poser.pugx.org/astra-techno/data-forge/v/stable)](https://packagist.org/packages/astra-techno/data-forge)
[![License](https://poser.pugx.org/astra-techno/data-forge/license)](https://packagist.org/packages/astra-techno/data-forge)

**DataForge** is a robust Laravel-based framework designed to streamline SQL query management, entity-based data handling, and modular task workflows. It helps developers build scalable, maintainable, and high-performance backend solutions for web applications.

---

## Key Features

- **Dynamic SQL Query Builder**: Write clean and reusable SQL queries using innovative features like `filterOptional` and `filterAnyOneRequired`.
- **Entity Management**: Encapsulate business logic with lazy-loading, inter-entity connectivity, and attribute grouping.
- **Modular Task Workflows**: Handle complex backend logic with reusable task classes.
- **Prebuilt API Endpoints**: Ready-to-use endpoints for fetching lists, single items, field values, and more.
- **Scalability & Flexibility**: Perfect for applications ranging from startups to enterprise-grade systems.

---

## Installation

Install the package via Composer:

```bash
composer require astra-techno/data-forge
```

---

## Getting Started

### 1. Publish Configuration and Setup
After installation, publish the configuration file and setup the directory structure:

```bash
php artisan vendor:publish --provider="AstraTech\DataForge\DataForgeServiceProvider"
```

This will create the `app/DataForge` directory, which includes `Sql`, `Entity`, and `Task` subdirectories.

---

### 2. Create a SQL Class
Define your SQL queries in a dedicated class:

```php
namespace App\DataForge\Sql;

class Product extends \DataForge\Sql
{
    public function list(&$data)
    {
        $query = Query('ProductList');
        $query->select('list', 'p.id, p.name, p.price, p.stock, c.name AS category');
        $query->from('products AS p');
        $query->inner('categories AS c ON p.category_id = c.id');
        $query->filterOptional('p.name LIKE {%request.keyword%}');
        $query->group('p.id');
        $query->order('{request.sort}', '{request.order}');
        return $query;
    }
}
```

---

### 3. Create an Entity Class
Define the business logic for your module:

```php
namespace App\DataForge\Entity;

class Product
{
    use \DataForge\Entity;

    function init($id)
    {
        return \Sql('Product:list', ['id' => $id, 'select' => 'entity'])->fetchRow();
    }

    function getCategory()
    {
        return \DataForge::getCategory($this->category_id);
    }
}
```

---

### 4. Use API Endpoints
Leverage prebuilt endpoints for data retrieval and task execution:

- **Fetch a list of products**:
  ```http
  GET /api/list/Product:list?keyword=shoes&pageNo=1&limit=10
  ```

- **Retrieve a product entity**:
  ```http
  GET /api/entity/Product/1
  ```

- **Execute a task**:
  ```http
  POST /api/Task/ProductTask/detail
  ```

---

## Documentation

Explore the full documentation, examples, and demo at:
🌐 **[DataForge Documentation](https://data-forge.tech)**

---

## Contributing

We welcome contributions to improve DataForge. Follow these steps to contribute:

1. Fork the repository.
2. Create a new branch for your feature or bug fix.
3. Commit your changes and push to your fork.
4. Submit a pull request.

---

## License

DataForge is open-source software licensed under the **MIT License**. See the [LICENSE](LICENSE) file for more details.

---

## Connect with Us

- **Official Site**: [data-forge.tech](https://data-forge.tech)
- **LinkedIn**: [Follow Us]([https://www.linkedin.com/company/dataforge](https://www.linkedin.com/company/astra-techno-data-forge))
- **Twitter**: [@DataForgeTech]([https://twitter.com/DataForgeTech](https://x.com/AstraTechno/))

---

## Support

If you encounter any issues or have questions, feel free to reach out through the [GitHub Issues](https://github.com/Astra-Techno/DataForge/issues) or our [support page]([https://data-forge.tech/support](https://data-forge.tech/support.html)).

---

**Build powerful backends with DataForge!**
