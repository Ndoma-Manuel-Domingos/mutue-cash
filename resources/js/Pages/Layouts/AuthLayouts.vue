<template>
    <!-- set progressbar -->
    <vue-progress-bar></vue-progress-bar>
    <slot />
</template>


<style>
  /*DEFAULT LOAD*/
  .ajax_load {
      display: none;
      position: fixed;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 9999999;
  }

  .ajax_load_box {
      margin: auto;
      text-align: center;
      color: #ffffff;
      font-weight: bold;
      text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.5);
  }

  .ajax_load_box_title{
    font-size: 20px;
    margin-top: 20px;
  }

  .ajax_load_box_circle {
      border: 10px solid #e3e3e3;
      border-top: 10px solid rgb(88, 193, 27);
      border-radius: 50%;
      margin: auto;
      margin-top: 400px;
      width: 120px;
      height: 120px;

      -webkit-animation: spin 1.2s linear infinite;
      -o-animation: spin 1.2s linear infinite;
      animation: spin 1.2s linear infinite;
  }

  @-webkit-keyframes spin {
      0% {
          -webkit-transform: rotate(0deg);
      }

      100% {
          -webkit-transform: rotate(360deg);
      }
  }

  @keyframes spin {
      0% {
          transform: rotate(0deg);
      }

      100% {
          transform: rotate(360deg);
      }
  }
</style>