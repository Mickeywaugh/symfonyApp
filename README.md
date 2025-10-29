本项目为个人常用的symfony框架项目库。
1. Symfony 7.3.x + Doctrine 3.5.x;
2. 封装了BaseCommand 基类，实现命令行功能，基本的start/stop操作外，还支持自定义方法；
3. 封装了BaseController基类，success/error方法，实现ajax返回数据功能.
4. 封装了BaseEntity基类,Entity继承此类,可以使用一些快捷方法来设置属性和将属性转换成数组;
5. 封装了BaseRepository基类,在Doctrine的ServiceEntityRepository类基础上，实现了较多的常用方法:
如page,entityPage,list,findEntities,getOptionList,findOrCreate,getCount,getFirst,wheres,create,batchCreate,update,flush等,这些方法的用法请请查看BaseRepository类中的方法注释,参数命名能反应其所代表的意义;
Repository类继承此类后可直接使用BaseRepository类中的方法进行数据库操作;
6. 封装了BaseService基类.
7. 封装了MailerService类,实现邮件发送功能;
8. 封装了RedisService类,实现基本的redis操作;
9. 封装了OscService类,支持aws s3存储;
10. 基于Doctrine/DBAL封装了DbalService类,实现直接使用sql对数据库进行操作;
11. 基于mpdf/mpdf和twig/twig,封装了PdfService类,实现twig生成html并生成pdf文档的功能;
12. 基于Monolog/Monolog,封装了Logger类,实现静态方法输出日志功能;
13. Redis,Symfony/Mailer等配置存在.env中，请自行修改对应配置的值。
14. 创建实体请参照Entity/UserEntity，创建仓库请参照Repository/UserRepository;如果使用php bin/console make:entity命令创建实体，请将实体继承BaseEntity类，参照Enntity/UserEntity和Repository/UserRepository类对entity和repository进行相应的修改;