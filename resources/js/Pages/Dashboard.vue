<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Dashboard</h1>
          </div>
          <div class="col-sm-6"></div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h4>{{ formatValor(total_depositado ?? 0) }}</h4>
                <p>Total Valor Depositado - Diário .</p>
              </div>
              <div class="icon">
                <i class="ion ion-bag"></i>
              </div>
              <Link :href="route('mc.depositos.index')" class="small-box-footer"
                >Mais detalhe <i class="fas fa-arrow-circle-right"></i
              ></Link>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h4>{{ formatValor(total_pagamento ?? 0) }}</h4>
                <p>Total de Pagamentos - Diário.</p>
              </div>
              <div class="icon">
                <i class="ion ion-stats-bars"></i>
              </div>
              <Link :href="route('mc.pagamentos.index')" class="small-box-footer"
                >Mais detalhe <i class="fas fa-arrow-circle-right"></i
              ></Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayouts>
</template>

<script>

import { Link } from "@inertiajs/inertia-vue3";

export default {
  props: [
    "total_depositado",
    "total_pagamento",
  ],
  components: {
    Link,
  },
  data() {
    return {};
  },

  mounted() {},
  methods: {
    formatValor(atual) {
      const valorFormatado = Intl.NumberFormat("pt-br", {
        style: "currency",
        currency: "AOA",
      }).format(atual);
      return valorFormatado;
    },
  },
};
</script>


