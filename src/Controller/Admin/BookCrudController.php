<?php namespace App\Controller\Admin;

use App\Entity\Book;

class BookCrudController extends CrudController {
	protected static $entityClass = Book::class;
}
