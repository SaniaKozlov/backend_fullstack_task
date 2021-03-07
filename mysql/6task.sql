--TASK 1
SELECT boosterpack.id, sum(boosterpack.price) AS busterpack_price, sum(transactions.amount) AS users_profit,
       date_format(transactions.time_cerated, '%Y-%m-%d %H:00') AS date
  FROM transactions
         RIGHT JOIN boosterpack
         ON boosterpack.id = transactions.ent_id
 WHERE transactions.ent_type = 'Model\\Boosterpack_model'
   AND type = 1
   AND info = 3
 GROUP BY boosterpack.id, date_format(transactions.time_cerated, '%Y-%m-%d %H:00');

 -- TASK 2
 SELECT u.id, u.personaname, sum(b.price) AS busterpack_price, sum(t.amount) AS users_profit,
        format(100 / sum(b.price) * sum(t.amount), 2) AS profit_percent,
        u.wallet_balance
   FROM transactions t
          RIGHT JOIN boosterpack b
          ON b.id = t.ent_id
          LEFT JOIN user u
          ON t.user_id = u.id
  WHERE t.ent_type = 'Model\\Boosterpack_model'
    AND type = 1
    AND info = 3
  GROUP BY u.id;

  -- UNION

  SELECT u.id, u.personaname, b.id AS boosterpack, sum(b.price) AS busterpack_price, sum(t.amount) AS users_profit,
         format(100 / sum(b.price) * sum(t.amount), 2) AS profit_percent,
         date_format(t.time_cerated, '%Y-%m-%d %H:00') AS date
    FROM transactions t
           RIGHT JOIN boosterpack b
           ON b.id = t.ent_id
           LEFT JOIN user u
           ON t.user_id = u.id
   WHERE t.ent_type = 'Model\\Boosterpack_model'
     AND type = 1
     AND info = 3
   GROUP BY b.id, u.id, date_format(t.time_cerated, '%Y-%m-%d %H:00');