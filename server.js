const http = require('http');
const fs = require('fs');
const path = require('path');
const mysql = require('mysql2/promise');

const PORT = process.env.PORT || 3000;

const db = mysql.createPool({
  host: 'kodama.proxy.rlwy.net',
  port: 48095,
  user: 'root',
  password: 'RQnEDOLRVYIVeeFfSoNGIzQStFgeMSCt',
  database: 'railway'
});
