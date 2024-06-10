<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-info">LISTAGEM DE SUBCONTA</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="/sub-contas">Dashboard</a></li>
              <li class="breadcrumb-item active">Listagem</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12 col-md-12">
            <div class="card">
              <div class="card-header"> 
                <a href="/listar-sub-contas/create" class="btn btn-info btn-sm mx-1"> <i class="fas fa-plus"></i> CRIAR SUBCONTAS</a>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-hover" id="tabela_de_subcontas">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Código</th>
                      <th>Nome</th>
                      <th>Conta</th>
                      <th>Tipo</th>
                      <th>Estado</th>
                      <th class="text-right" style="width: 200px;">Ações</th>
                    </tr>
                  </thead>
                  
                  <tbody>
                    <tr v-for="item in sub_contas" :key="item">
                      <td>#</td>
                      <td>{{ item.numero }}</td>
                      <td>{{ item.designacao }}</td>
                      <td>{{ item.conta.numero }} - {{ item.conta.designacao }}</td>
                      <td>{{ item.tipo }}</td>
                      <td class="text-capitalize">{{ item.estado }}</td>
                      <td>
                        <div class="float-right">
                          <a :href="`/listar-sub-contas/${item.id}/edit`" class="btn btn-sm btn-success"><i class="fas fa-edit"></i> Editar</a>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayouts>
</template>
  
<script>
export default {

  props: [
    'sub_contas'
  ],
  computed: {
  },
  data() {
    return {
    };
  },
  mounted() {
    $('#tabela_de_subcontas').DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": true,
    });
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
  },
  methods: {
    updateData() {
      this.$Progress.start();
      this.$inertia.get("/sub-contas", this.params, {
        preserveState: true,
        preverseScroll: true,
        onSuccess: () => {
          this.$Progress.finish();
        },
      });
    },
    
    mudar_estado(item) {
      this.$Progress.start();

      axios.get(`/sub-contas/${item.id}`)
        .then((response) => {
          this.$Progress.finish();
          Swal.fire({
            toast: true,
            icon: "success",
            title: "Estado Alterado com sucesso!",
            animation: false,
            position: "top-end",
            showConfirmButton: false,
            timer: 4000
          })
      
          window.location.reload();
        })
        .catch((error) => {
          
          this.$Progress.fail();
          Swal.fire({
            toast: true,
            icon: "danger",
            title: "Correu um erro ao Estado Alterado com sucesso!",
            animation: false,
            position: "top-end",
            showConfirmButton: false,
            timer: 4000
          })
          
        });
    },
  
    imprimirContas() {
      window.open("imprimir-sub-contas");
    },
  },
};
</script>
  
  