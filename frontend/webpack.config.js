const path = require("path");
const HtmlWebpackPlugin = require("html-webpack-plugin");
const NodePolyfillPlugin = require("node-polyfill-webpack-plugin");
const Dotenv = require("dotenv-webpack");

module.exports = {
  mode: "production",
  entry: "./src/index.js",
  resolve: {
    fallback: {
      net: false,
      tls: false,
      fs: false,
    },
  },
  externals: {
    ethers: 'ethers', //load ethers from CDN
  },
  output: {
    filename: "main.js",
    path: path.resolve(__dirname, "dist")
  },
  plugins: [
    new HtmlWebpackPlugin({
      template: "./src/index.html",
      filename: "index.html",
    }),
    new NodePolyfillPlugin(),
    new Dotenv(),
  ],
};
