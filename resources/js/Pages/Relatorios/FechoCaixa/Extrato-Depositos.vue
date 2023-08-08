<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Extratos de Depositos</h1>
          </div>
          <div class="col-sm-6"></div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12 col-md-12">
            <div class="card">
              <form action="">
                <div class="card-body">
                  <div class="row">
                    <div class="col-12 col-md-2">
                      <div class="form-group">
                        <label for="">Data Inicio</label>
                        <input
                          type="date"
                          placeholder="informe do Inicio"
                          class="form-control"
                          v-model="data_inicio"
                        />
                      </div>
                    </div>

                    <div class="col-12 col-md-2">
                      <div class="form-group">
                        <label for="">Data Final</label>
                        <input
                          type="date"
                          placeholder="informe do final"
                          class="form-control"
                          v-model="data_final"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12 col-md-12">
            <div class="card">
              <div class="card-header">
              
                <button
                  class="btn btn-info float-left mr-1"
                  type="button"
                >
                  <i class="fas fa-money-check-alt"></i>
                  TOTAL: {{ formatValor(valor_deposito) }}
                </button>
                
                <button
                  class="btn btn-success float-right mr-1"
                  type="button"
                  @click="imprimirEXCEL"
                >
                  <i class="fas fa-file-excel"></i>
                  EXCEL
                </button>

                <button
                  class="btn btn-danger float-right mr-1"
                  type="button"
                  @click="imprimirPDF"
                >
                  <i class="fas fa-file-pdf"></i>
                  PDF
                </button>
              </div>

              <div class="table-responsive">
                <table class="table table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>Nº Deposito</th>
                      <th>Matricula</th>
                      <th>Estudante</th>
                      <th>Saldo depositado</th>
                      <th>Saldo após Movimento</th>
                      <!-- <th>Forma Pagamento</th> -->
                      <th>Operador</th>
                      <!-- <th>Ano Lectivo</th> -->
                      <th>Data</th>
                      <th>Acções</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in items.data" :key="item.codigo">
                      <td>{{ item.codigo }}</td>
                      <td>{{ item.codigo_matricula_id }}</td>
                      <td>
                        {{ item.matricula.admissao.preinscricao.Nome_Completo }}
                      </td>
                      <td>{{ formatValor(item.valor_depositar) }}</td>
                      <td>{{ formatValor(item.saldo_apos_movimento) }}</td>
                      <!-- <td>{{ item.forma_pagamento.descricao }}</td> -->
                      <td>{{ item.user ? item.user.nome : "" }}</td>
                      <!-- <td>{{ item.ano_lectivo.Designacao ?? '' }}</td> -->
                      <td>{{ item.created_at }}</td>
                      <td class="text-center">
                        <Link @click="imprimirComprovativo(item)">
                          <i class="fas fa-print text-danger"></i>
                        </Link>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="card-footer">
                <Link href="" class="text-secondary">
                  TOTAL REGISTROS: {{ items.total }}
                </Link>
                <Paginacao
                  :links="items.links"
                  :prev="items.prev_page_url"
                  :next="items.next_page_url"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayouts>
</template>
    
<script>
import Paginacao from "../../../Shared/Paginacao";
import { Link } from "@inertiajs/inertia-vue3";

export default {
  props: ["items", "valor_deposito"],
  components: { Link, Paginacao },
  data() {
    return {
      data_inicio: new Date().toISOString().substr(0, 10),
      data_final: new Date().toISOString().substr(0, 10),
      operador: "",

      depositos: [],

      params: {},
    };
  },
  watch: {
    options: function (val) {
      this.params.page = val.page;
      this.params.page_size = val.itemsPerPage;
      if (val.sortBy.length != 0) {
        this.params.sort_by = val.sortBy[0];
        this.params.order_by = val.sortDesc[0] ? "desc" : "asc";
      } else {
        this.params.sort_by = null;
        this.params.order_by = null;
      }
      this.updateData();
    },
    data_inicio: function (val) {
      this.params.data_inicio = val;
      this.updateData();
    },
    data_final: function (val) {
      this.params.data_final = val;
      this.updateData();
    },
  },
  methods: {
    updateData() {
      this.$Progress.start();
      this.$inertia.get("/relatorios/extrato-depositos", this.params, {
        preserveState: true,
        preverseScroll: true,
        onSuccess: () => {
          this.$Progress.finish();
        },
      });
    },

    formatValor(atual) {
      const valorFormatado = Intl.NumberFormat("pt-br", {
        style: "currency",
        currency: "AOA",
      }).format(atual);
      return valorFormatado;
    },

    imprimirComprovativo(item) {
      window.open(
        `/depositos/imprimir-comprovativo?codigo=${item.codigo}`,
        "_blank"
      );
    },

    imprimirPDF() {
      window.open(`/relatorios/fecho-caixa/operador/pdf`, "_blank");
    },

    imprimirEXCEL() {
      window.open(`/relatorios/fecho-caixa/operador/excel`, "_blank");
    },
  },
};
</script>
      
      