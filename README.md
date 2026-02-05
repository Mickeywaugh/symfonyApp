This project is a personal commonly used Symfony framework project library.

1. Symfony 7.3.x + Doctrine 3.5.x;
2. Encapsulated BaseCommand base class to implement command-line functionality, supporting basic start/stop operations as well as custom methods;
3. Encapsulated BaseController base class with success/error methods to implement AJAX data return functionality;
4. Encapsulated BaseEntity base class; entities inheriting this class can use some shortcut methods to set properties and convert properties into arrays;
5. Encapsulated BaseRepository base class, which extends Doctrine's ServiceEntityRepository class and implements many commonly used methods:
   Such as page, entityPage, list, findEntities, getOptionList, findOrCreate, getCount, getFirst, wheres, create, batchCreate, update, flush, etc. Please refer to the method comments in the BaseRepository class for usage. Parameter names reflect their meanings;
   Repository classes that inherit this class can directly use the methods in the BaseRepository class for database operations;
6. Encapsulated BaseService base class;
7. Encapsulated MailerService class to implement email sending functionality;
8. Encapsulated RedisService class to implement basic Redis operations;
9. Encapsulated OscService class to support AWS S3 storage;
10. Based on Doctrine/DBAL, encapsulated DbalService class to implement direct SQL database operations;
11. Based on mpdf/mpdf and twig/twig, encapsulated PdfService class to implement generating HTML from Twig and creating PDF documents;
12. Based on Monolog/Monolog, encapsulated Logger class to implement static method log output functionality;
13. Configurations for Redis, Symfony/Mailer, etc., are stored in .env; please modify the corresponding configuration values as needed;
14. For creating entities, please refer to Entity/UserEntity; for creating repositories, please refer to Repository/UserRepository; if using the php bin/console make:entity command to create entities, please have the entity inherit the BaseEntity class and modify the entity and repository accordingly by referring to the Enntity/UserEntity and Repository/UserRepository classes;
15. Added Message class to implement asynchronous messaging functionality; please modify the MESSENGER_TRANSPORT_DSN configuration item in the environment variable .env file. Use the php bin/console messenger:consume async command to start the asynchronous messaging service. In controllers, please use container injection of the Symfony\Component\Messenger\MessageBusInterface interface, and create asynchronous message entities with new AsyncMsg();